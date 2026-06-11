<?php
/**
 * Unit tests for the Pro Membership Analytics data layer.
 *
 * Covers the pure reducers in WPS_Membership_Analytics:
 *   - aggregate()      window metrics + per-plan breakdown
 *   - previous_range() equal-length adjacent comparison window
 *   - delta()          per-value change, incl. divide-by-zero handling
 *   - compare()        full comparison across all metric keys
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the analytics
 * class file is required directly — its reducers depend only on the input
 * arrays, not on WordPress or the database.
 *
 * @since   2.7.0
 * @package Subscriptions_For_Woocommerce
 */

class MembershipAnalyticsTest extends WP_UnitTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_class = dirname( __DIR__, 4 )
			. '/woocommerce-subscriptions-pro/includes/pro/class-wps-membership-analytics.php';
		if ( file_exists( $pro_class ) ) {
			require_once $pro_class;
		}
	}

	/**
	 * Build a membership row with sensible defaults.
	 *
	 * @param array $overrides Field overrides.
	 * @return array
	 */
	private function row( array $overrides = array() ) {
		return array_merge(
			array(
				'plan_slug'   => 'gold',
				'start_date'  => 1500,
				'updated_at'  => 1500,
				'status'      => 'active',
				'expiry_date' => null,
				'amount'      => 0.0,
			),
			$overrides
		);
	}

	// -----------------------------------------------------------------------
	// aggregate()
	// -----------------------------------------------------------------------

	public function test_new_members_counts_only_starts_inside_window() {
		$rows = array(
			$this->row( array( 'start_date' => 1500 ) ), // in.
			$this->row( array( 'start_date' => 1000 ) ), // in (inclusive lower bound).
			$this->row( array( 'start_date' => 2000 ) ), // in (inclusive upper bound).
			$this->row( array( 'start_date' => 500 ) ),  // out (before).
			$this->row( array( 'start_date' => 2500 ) ), // out (after).
		);

		$result = WPS_Membership_Analytics::aggregate( $rows, 1000, 2000 );

		$this->assertSame( 3, $result['new_members'] );
	}

	public function test_cancellations_count_cancelled_and_expired_by_updated_at() {
		$rows = array(
			$this->row( array( 'status' => 'cancelled', 'updated_at' => 1200 ) ), // in.
			$this->row( array( 'status' => 'expired', 'updated_at' => 1800 ) ),   // in.
			$this->row( array( 'status' => 'cancelled', 'updated_at' => 500 ) ),  // out.
			$this->row( array( 'status' => 'active', 'updated_at' => 1500 ) ),    // not a cancellation.
		);

		$result = WPS_Membership_Analytics::aggregate( $rows, 1000, 2000 );

		$this->assertSame( 2, $result['cancellations'] );
	}

	public function test_active_snapshot_respects_expiry_and_start() {
		$rows = array(
			$this->row( array( 'status' => 'active', 'start_date' => 1500, 'expiry_date' => null ) ),  // active, lifetime.
			$this->row( array( 'status' => 'active', 'start_date' => 1500, 'expiry_date' => 5000 ) ),  // active, expires after window.
			$this->row( array( 'status' => 'active', 'start_date' => 1500, 'expiry_date' => 1800 ) ),  // expired by window end.
			$this->row( array( 'status' => 'active', 'start_date' => 2500, 'expiry_date' => null ) ),  // started after window end.
			$this->row( array( 'status' => 'cancelled', 'start_date' => 1500, 'expiry_date' => null ) ), // not active.
		);

		$result = WPS_Membership_Analytics::aggregate( $rows, 1000, 2000 );

		$this->assertSame( 2, $result['active_members'] );
	}

	public function test_revenue_sums_amount_for_new_members_only() {
		$rows = array(
			$this->row( array( 'start_date' => 1500, 'amount' => 30.0 ) ),  // in → counted.
			$this->row( array( 'start_date' => 1600, 'amount' => 19.5 ) ),  // in → counted.
			$this->row( array( 'start_date' => 500, 'amount' => 99.0 ) ),   // out → ignored.
		);

		$result = WPS_Membership_Analytics::aggregate( $rows, 1000, 2000 );

		$this->assertEqualsWithDelta( 49.5, $result['revenue'], 0.001 );
	}

	public function test_breakdown_by_plan_is_keyed_and_summed() {
		$rows = array(
			$this->row( array( 'plan_slug' => 'gold', 'start_date' => 1500, 'amount' => 10.0 ) ),
			$this->row( array( 'plan_slug' => 'gold', 'start_date' => 1600, 'amount' => 5.0 ) ),
			$this->row( array( 'plan_slug' => 'silver', 'start_date' => 1700, 'amount' => 8.0 ) ),
		);

		$result = WPS_Membership_Analytics::aggregate( $rows, 1000, 2000 );

		$this->assertSame( 2, $result['by_plan']['gold']['new_members'] );
		$this->assertEqualsWithDelta( 15.0, $result['by_plan']['gold']['revenue'], 0.001 );
		$this->assertSame( 1, $result['by_plan']['silver']['new_members'] );
		$this->assertEqualsWithDelta( 8.0, $result['by_plan']['silver']['revenue'], 0.001 );
	}

	public function test_aggregate_returns_zeroed_metrics_for_empty_rows() {
		$result = WPS_Membership_Analytics::aggregate( array(), 1000, 2000 );

		$this->assertSame( 0, $result['new_members'] );
		$this->assertSame( 0, $result['active_members'] );
		$this->assertSame( 0, $result['cancellations'] );
		$this->assertEqualsWithDelta( 0.0, $result['revenue'], 0.001 );
		$this->assertSame( array(), $result['by_plan'] );
	}

	// -----------------------------------------------------------------------
	// series()
	// -----------------------------------------------------------------------

	public function test_series_returns_one_value_per_bucket() {
		$result = WPS_Membership_Analytics::series( array(), 1000, 2000, 5 );

		$this->assertCount( 5, $result['labels'] );
		$this->assertCount( 5, $result['new_members'] );
		$this->assertCount( 5, $result['cancellations'] );
		$this->assertCount( 5, $result['revenue'] );
	}

	public function test_series_buckets_new_members_into_correct_slice() {
		// Window 0..1000 split into 4 buckets of 250 each:
		//   bucket 0: 0..249, 1: 250..499, 2: 500..749, 3: 750..1000.
		$rows = array(
			$this->row( array( 'start_date' => 100, 'amount' => 5.0 ) ),  // bucket 0.
			$this->row( array( 'start_date' => 300, 'amount' => 7.0 ) ),  // bucket 1.
			$this->row( array( 'start_date' => 350, 'amount' => 3.0 ) ),  // bucket 1.
			$this->row( array( 'start_date' => 900, 'amount' => 9.0 ) ),  // bucket 3.
		);

		$result = WPS_Membership_Analytics::series( $rows, 0, 1000, 4 );

		$this->assertSame( array( 1, 2, 0, 1 ), $result['new_members'] );
		$this->assertEqualsWithDelta( 5.0, $result['revenue'][0], 0.001 );
		$this->assertEqualsWithDelta( 10.0, $result['revenue'][1], 0.001 );
		$this->assertEqualsWithDelta( 9.0, $result['revenue'][3], 0.001 );
	}

	public function test_series_buckets_cancellations_by_updated_at() {
		$rows = array(
			$this->row( array( 'status' => 'cancelled', 'updated_at' => 100 ) ),  // bucket 0.
			$this->row( array( 'status' => 'expired', 'updated_at' => 800 ) ),    // bucket 3.
			$this->row( array( 'status' => 'active', 'updated_at' => 800 ) ),     // not counted.
		);

		$result = WPS_Membership_Analytics::series( $rows, 0, 1000, 4 );

		$this->assertSame( array( 1, 0, 0, 1 ), $result['cancellations'] );
	}

	public function test_series_clamps_bucket_count_to_at_least_one() {
		$rows   = array( $this->row( array( 'start_date' => 500 ) ) );
		$result = WPS_Membership_Analytics::series( $rows, 0, 1000, 0 );

		$this->assertCount( 1, $result['new_members'] );
		$this->assertSame( array( 1 ), $result['new_members'] );
	}

	// -----------------------------------------------------------------------
	// previous_range()
	// -----------------------------------------------------------------------

	public function test_previous_range_is_equal_length_and_adjacent() {
		list( $prev_from, $prev_to ) = WPS_Membership_Analytics::previous_range( 300000, 400000 );

		$this->assertSame( 299999, $prev_to, 'Previous window ends one second before the current window.' );
		$this->assertSame( 199999, $prev_from, 'Previous window spans the same duration as the current one.' );
		$this->assertSame( 400000 - 300000, $prev_to - $prev_from );
	}

	public function test_previous_range_clamps_at_zero() {
		list( $prev_from, $prev_to ) = WPS_Membership_Analytics::previous_range( 100, 5000 );

		$this->assertGreaterThanOrEqual( 0, $prev_from );
		$this->assertGreaterThanOrEqual( 0, $prev_to );
	}

	// -----------------------------------------------------------------------
	// delta()
	// -----------------------------------------------------------------------

	public function test_delta_growth_and_decline() {
		$up = WPS_Membership_Analytics::delta( 15, 10 );
		$this->assertSame( 'up', $up['direction'] );
		$this->assertEqualsWithDelta( 50.0, $up['pct'], 0.001 );

		$down = WPS_Membership_Analytics::delta( 5, 10 );
		$this->assertSame( 'down', $down['direction'] );
		$this->assertEqualsWithDelta( -50.0, $down['pct'], 0.001 );
	}

	public function test_delta_handles_zero_previous() {
		$from_zero = WPS_Membership_Analytics::delta( 8, 0 );
		$this->assertSame( 'up', $from_zero['direction'] );
		$this->assertEqualsWithDelta( 100.0, $from_zero['pct'], 0.001 );

		$both_zero = WPS_Membership_Analytics::delta( 0, 0 );
		$this->assertSame( 'flat', $both_zero['direction'] );
		$this->assertEqualsWithDelta( 0.0, $both_zero['pct'], 0.001 );
	}

	// -----------------------------------------------------------------------
	// compare()
	// -----------------------------------------------------------------------

	public function test_compare_covers_every_metric_key() {
		$current  = array(
			'new_members'    => 10,
			'active_members' => 50,
			'cancellations'  => 2,
			'revenue'        => 200.0,
		);
		$previous = array(
			'new_members'    => 5,
			'active_members' => 40,
			'cancellations'  => 4,
			'revenue'        => 100.0,
		);

		$comparison = WPS_Membership_Analytics::compare( $current, $previous );

		$this->assertSame(
			array( 'new_members', 'active_members', 'cancellations', 'revenue' ),
			array_keys( $comparison )
		);
		$this->assertSame( 'up', $comparison['new_members']['direction'] );
		$this->assertSame( 'down', $comparison['cancellations']['direction'] );
		$this->assertEqualsWithDelta( 100.0, $comparison['revenue']['pct'], 0.001 );
	}
}
