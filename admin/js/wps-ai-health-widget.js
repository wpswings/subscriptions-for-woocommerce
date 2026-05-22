(function($) {
	'use strict';

	var config = window.wpsAiHealthWidget || {};

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function renderGroups(groups) {
		var html = '';
		var recommendationCount = 0;

		(groups || []).forEach(function(group) {
			var items = group && Array.isArray(group.items) ? group.items : [];

			if (!group) {
				return;
			}

			recommendationCount += items.length;
			html += '<div class="wps-ai-health-insight-card wps-ai-health-insight-card--' + escapeHtml(group.key || 'actions') + '">';
			html += '<div class="wps-ai-health-insight-card__head">';
			html += '<span class="wps-ai-health-insight-card__dot" aria-hidden="true"></span>';
			html += '<strong>' + escapeHtml(group.title) + '</strong>';
			html += '<span>' + items.length + '</span>';
			html += '</div>';
			html += '<p>' + escapeHtml(group.description) + '</p>';
			html += '<ul>';
			items.forEach(function(item) {
				html += '<li>' + escapeHtml(item) + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		});

		$('[data-wps-ai-health-recommendation-count]').text(recommendationCount + ' recommendations');
		return html;
	}

	function renderState(state) {
		if (!state || !state.result) {
			return;
		}

		if (state.counts) {
			if (state.counts.statuses) {
				$('[data-wps-ai-health-count="active"]').text(state.counts.statuses.active || 0);
				$('[data-wps-ai-health-count="on-hold"]').text(state.counts.statuses['on-hold'] || 0);
			}
			$('[data-wps-ai-health-count="new_this_week"]').text(state.counts.new_this_week || 0);
			$('[data-wps-ai-health-count="expiring_soon"]').text(state.counts.expiring_soon || 0);
			$('[data-wps-ai-health-count="upcoming_renewal"]').text(state.counts.upcoming_billings_30_days || 0);
		}

		$('[data-wps-ai-health-groups]').html(renderGroups(state.result.groups));
		$('[data-wps-ai-health-generated]').text(state.result.generated_label || 'Generated just now');
	}

	$(document).on('click', '[data-wps-ai-health-refresh]', function(event) {
		event.preventDefault();

		var $button = $(this);
		var original = $button.text();

		$button.prop('disabled', true).text(config.refreshing || 'Refreshing analysis...');

		$.ajax({
			url: config.ajaxurl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: config.refreshAction,
				nonce: config.nonce
			}
		}).done(function(response) {
			if (response && response.success && response.data && response.data.state) {
				renderState(response.data.state);
				return;
			}

			window.alert(config.errorText || 'Unable to refresh AI Insights.');
		}).fail(function(xhr) {
			var message = config.errorText || 'Unable to refresh AI Insights.';
			if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				message = xhr.responseJSON.data.message;
			}
			window.alert(message);
		}).always(function() {
			$button.prop('disabled', false).text(original || config.refreshText || 'Refresh analysis');
		});
	});

})(jQuery);
