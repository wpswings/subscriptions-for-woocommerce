<?php
namespace includes;

class standardPluginTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;
    
    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
        $user_id = $this->factory->user->create( array('role' => 'administrator') );
        wp_set_current_user($user_id);
        set_current_screen('edit.php');
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    // Tests
//    public function test_it_works()
//    {
//        $post = static::factory()->post->create_and_get();
//
//        $this->assertInstanceOf(\WP_Post::class, $post);
//    }

    // check if all admin classes are loaded.
    public function test_it_works()
    {
        $classes = [];
        $classes[] = 'Subscriptions_For_Woocommerce';

        $this->assertTrue( is_admin() );
        foreach ($classes as $class)
        {
            $this->assertTrue( class_exists( $class, false ) );
        }
    }
}
