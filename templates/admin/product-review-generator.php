<?php
global $post;
//set end_date  = today, start_date = today - 30 days
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));
$default_count = get_option('openai_tools_review_count', 10);
?>

<div class="ai-contanier">
	<div class="ai-reviewer">
		<div class="ai-reviewer__content">
			<div class="ai-reviewer__content__item">
				<label for="ai-model">Model</label>
				<?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?>
			</div>
			<div class="ai-reviewer__content__item">
				<label for="review-count">Count</label>
				<input type="number" id="review-count" name="review-count" value="<?php echo esc_html($default_count) ?>" max="20" min="1">
			</div>
			<div class="ai-reviewer__content__item">
				<label for="start-date">Start Date</label>
				<input type="date" id="start-date" name="start-date" value="<?php echo esc_html($start_date); ?>">
			</div>
			<div class="ai-reviewer__content__item">
				<label for="end-date">End Date</label>
				<input type="date" id="end-date" name="end-date" value="<?php echo esc_html($end_date); ?>">
			</div>
			<div class="ai-reviewer__content__item">
				<label for="avg-rating">Avg Rating</label>
				<input type="number" id="avg-rating" name="avg-rating" value="5" min="1" max="5">
			</div>
			<div class="ai-reviewer__content__item">
				<label for="topics">Topics</label>
				<input type="text" id="topics" name="topics" placeholder="Easy to use, Good quality">
			</div>
			<div class="ai-reviewer__content__item">
				<label for="languages">Language</label>
				<select id="languages" name="language">
					<?php include OPENAI_TOOLS_DIR . "templates/language-options.php" ?>
				</select>
			</div>

			<div class="ai-reviewer__content__item" style="display: flex; justify-content: space-between; align-items: center;">
				<a id="submit-review" class="button button-primary" <?php echo esc_html($disable_submit); ?>>Submit</a>
				<?php if ($disable_submit == 'disabled') : ?>
					<div class="ai-commenter__loading">
						<p style="color: red;">Please set your AI API Key in the <a href="<?php echo admin_url('admin.php?page=ai-tools-settings'); ?>">settings page</a></p>
					</div>
				<?php endif; ?>
				<div class="ai-reviewer__loading" style="display: none;">
					<img src="<?php echo OPENAI_TOOLS_URL; ?>/assets/img/loading.gif" alt="loading" style="width: 32px;">
				</div>
			</div>
		</div>
		<div class="ai-reviewer__generated_reviews">
		</div>
		<div class="ai-reviewer__add_reviews">
			<a id="add-review" class="button">Add Reviews</a>
			<div class="ai-reviewer__add_loading" style="display: none;">
				<img src="<?php echo OPENAI_TOOLS_URL; ?>/assets/img/loading.gif" alt="loading" style="width: 32px;">
			</div>
		</div>
	</div>
</div>

<style>
	.ai-reviewer__content__item {
		margin-bottom: 10px;
	}

	.ai-reviewer__content__item input[type=number] {
		width: 100px;
		text-align: center;
	}

	.ai-reviewer__generated_reviews__item {
		display: flex;
		margin-bottom: 10px;
		justify-content: space-between;
	}

	.ai-reviewer__generated_reviews__item__content {
		margin-left: 10px;
		width: 100%;
		display: flex;
		gap: 10px;
	}

	.ai-reviewer__content__item,
	.ai-reviewer__add_reviews.show {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.ai-reviewer__generated_reviews__item__content__detail {
		width: 100%;
	}

	.ai-reviewer__generated_reviews__item__content__review {
		padding-top: 10px;
	}

	.ai-reviewer__generated_reviews__item__content__info {
		display: flex;
		gap: 10px;
	}

	.ai-reviewer__generated_reviews__item__content__review textarea {
		width: 100%;
	}

	.ai-reviewer__add_reviews {
		-webkit-animation: show 0.5s;
		animation: show 0.5s;
		display: none;
	}

	.ai-reviewer__add_reviews.show {
		-webkit-animation: show 0.5s;
		animation: show 0.5s;
	}

	@-webkit-keyframes show {
		0% {
			opacity: 0;
			max-height: 0;
		}

		100% {
			opacity: 1;
			max-height: 30px;
		}
	}
</style>

<script>
	<?php if ($disable_submit == '') : ?>
		jQuery(document).ready(function($) {
			$('#submit-review').click(function() {
				var ai_model = $('.ai-reviewer #ai-model').val();
				var review_count = $('#review-count').val();
				var start_date = $('#start-date').val();
				var end_date = $('#end-date').val();
				var avg_rating = $('#avg-rating').val();
				var topics = $('#topics').val();
				var languages = $('#languages').val();
				var product_id = <?php echo $post->ID; ?>;
				$('.ai-reviewer__loading').show();
				$('.ai-reviewer__generated_reviews').html('');

				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'ai_tools_generate_reviews',
						ai_model: ai_model,
						count: review_count,
						start_date: start_date,
						end_date: end_date,
						avg_rating: avg_rating,
						topics: topics,
						languages: languages,
						product_id: product_id,
						_wpnonce: '<?php echo wp_create_nonce('ai_tools_generate_reviews'); ?>'
					},
					success: function(response) {
						console.log(response);
						$('.ai-reviewer__loading').hide();

						$('.ai-reviewer__generated_reviews').html('');
						$.each(response, function(index, value) {
							//if value.reviewer is an object, then get the name
							if (typeof value.reviewer === 'object') {
								value.reviewer = value.reviewer.name;
							}
							//show checkbox and reviewer field, rating field, date field and review textarea 
							$('.ai-reviewer__generated_reviews').append(`
							<div class="ai-reviewer__generated_reviews__item">
								<div class="ai-reviewer__generated_reviews__item__content">
									<div class="ai-reviewer__generated_reviews__item__content__checkbox">
										<input type="checkbox" name="review-item" value="` + value.id + `">
									</div>
									<div class="ai-reviewer__generated_reviews__item__content__detail">
										<div class="ai-reviewer__generated_reviews__item__content__info">
											<input type="text" name="reviewer" value="` + value.reviewer + `" placeholder="Reviewer">
											<input type="number" name="rating" value="` + value.rating + `" min="1" max="5" placeholder="Rating">
											<input type="date" name="date" value="` + value.date + `" placeholder="Date">
										</div>
										<div class="ai-reviewer__generated_reviews__item__content__review">
										<textarea name="review" rows="4" placeholder="Review">` + value.detail + `</textarea>
									</div>
									</div>
								</div>
							</div>
						`);
						});

						//add select all button
						$('.ai-reviewer__generated_reviews').prepend(`
						<div class="ai-reviewer__generated_reviews__select_all" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;">
							<div class="ai-reviewer__generated_reviews__select_all__title">Select All</div>
							<input type="checkbox" id="select-all">
						</div>
					`);

						$('#select-all').click(function() {
							if ($(this).is(':checked')) {
								$('input[name="review-item"]').prop('checked', true);
								$('.ai-reviewer__add_reviews').addClass('show');
							} else {
								$('input[name="review-item"]').prop('checked', false);
								$('.ai-reviewer__add_reviews').removeClass('show');
							}
						});

						$('input[name="review-item"]').click(function() {
							if ($('input[name="review-item"]:checked').length > 0) {
								$('.ai-reviewer__add_reviews').addClass('show');
							} else {
								$('.ai-reviewer__add_reviews').removeClass('show');
							}
						});

						//remvoe all click events from #add-review
						$('#add-review').off('click');
						// add reviews to product
						$('#add-review').click(function() {
							// show loading
							$('.ai-reviewer__add_loading').show();
							// get all checked reviews
							var reviews = [];
							$('input[name="review-item"]:checked').each(function() {
								reviews.push({
									id: $(this).val(),
									reviewer: $(this).parent().parent().find('input[name="reviewer"]').val(),
									rating: $(this).parent().parent().find('input[name="rating"]').val(),
									date: $(this).parent().parent().find('input[name="date"]').val(),
									review: $(this).parent().parent().find('textarea[name="review"]').val()
								});
							});

							//check reviews count
							if (reviews.length == 0) {
								alert('Please select at least one review!');
								$('.ai-reviewer__add_loading').hide();
								return;
							}

							// send ajax request to add reviews to product
							$.ajax({
								url: '<?php echo admin_url('admin-ajax.php'); ?>',
								type: 'POST',
								data: {
									action: 'openai_tools_add_reviews',
									reviews: reviews,
									product_id: product_id,
									_wpnonce: '<?php echo wp_create_nonce('openai_tools_add_reviews'); ?>'
								},
								success: function(response) {
									console.log(response);
									$('.ai-reviewer__add_loading').hide();
									//show success message
									$('.ai-reviewer__add_reviews').append(`
									<div class="ai-reviewer__add_reviews__success" style="color: green;">Add Done!</div>
								`);
									//hide success message after 3 seconds
									setTimeout(function() {
										$('.ai-reviewer__add_reviews__success').remove();
									}, 3000);
								},
								error: function(error) {
									console.log(error);
								}
							});
						});

					},
					error: function(error) {
						console.log(error);
					}
				});
			});
		});
	<?php endif; ?>
</script>

<?php

include OPENAI_TOOLS_DIR . 'templates/admin/yoast-ai-tools.php';
