<?php
global $post;
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime($post->post_date));
$default_count = get_option('openai_tools_comment_count', 10);
?>

<div class="ai-contanier">
	<div class="ai-commenter">
		<div class="ai-commenter__content__item">
			<label for="ai-model">Model</label>
			<?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?>
		</div>
		<div class="ai-commenter__content__item">
			<label for="comment-counts">Count</label>
			<input type="number" id="comment-counts" name="comment-counts" value="<?php echo esc_html($default_count) ?>" max="20" min="1">
		</div>
		<div class="ai-commenter__content__item">
			<label for="start-date">Start Date</label>
			<input type="date" id="start-date" name="start-date" value="<?php echo esc_html($start_date); ?>">
		</div>
		<div class="ai-commenter__content__item">
			<label for="end-date">End Date</label>
			<input type="date" id="end-date" name="end-date" value="<?php echo esc_html($end_date); ?>">
		</div>
		<div class="ai-commenter__content__item">
			<label for="keywords">Keywords</label>
			<input type="text" id="keywords" name="keywords" placeholder="Thank you.">
		</div>
		<div class="ai-commenter__content__item">
			<label for="languages">Language</label>
			<select id="languages" name="languages">
				<?php include OPENAI_TOOLS_DIR . "templates/language-options.php" ?>
			</select>
		</div>
		<div class="ai-commenter__content__item" style="display: flex; justify-content: space-between; align-items: center;">
			<a id="submit-comment" class="button button-primary" <?php echo esc_html($disable_submit); ?>>Submit</a>
			<?php if ($disable_submit == 'disabled') : ?>
				<div class="ai-commenter__loading">
					<p style="color: red;">Please set your AI API Key in the <a href="<?php echo admin_url('admin.php?page=openai-tools-settings'); ?>">settings page</a></p>
				</div>
			<?php endif; ?>
			<div class="ai-commenter__loading" style="display: none;">
				<img src="<?php echo OPENAI_TOOLS_URL; ?>/assets/img/loading.gif" alt="loading" style="width: 32px;">
			</div>
		</div>
		<div class="ai-commenter__generated_comments">
		</div>
		<div class="ai-commenter__add_comments">
			<a id="add-comment" class="button">Add Comments</a>
			<div class="ai-commenter__add_loading" style="display: none;">
				<img src="<?php echo OPENAI_TOOLS_URL; ?>/assets/img/loading.gif" alt="loading" style="width: 32px;">
			</div>
		</div>
	</div>
</div>

<style>
	.ai-commenter__content__item {
		margin-bottom: 10px;
	}

	.ai-commenter__content__item input[type=number] {
		width: 100px;
		text-align: center;
	}

	.ai-commenter__generated_comments__item {
		display: flex;
		margin-bottom: 10px;
		justify-content: flex-start;
		gap: 10px;
		align-items: center;
	}

	.ai-commenter__generated_comments__item__content {
		width: 100%;
		display: flex;
		gap: 10px;
	}

	.ai-commenter__generated_comments__item__content__detail {
		width: 100%;
	}

	.ai-commenter__generated_comments__item__content__comment {
		padding-top: 10px;
	}

	.ai-commenter__generated_comments__item__content__info {
		display: flex;
		gap: 10px;
	}

	.ai-commenter__generated_comments__item__content__comment textarea {
		width: 100%;
	}

	.ai-commenter__content__item,
	.ai-commenter__add_comments.show {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.ai-commenter__add_comments {
		-webkit-animation: show 0.5s;
		animation: show 0.5s;
		display: none;
	}

	.ai-commenter__add_comments.show {
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
			$('#submit-comment').click(function() {
				$('.ai-commenter__loading').show();
				$('.ai-commenter__generated_comments').html('');
				$('.ai-commenter__add_loading').hide();
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'openai_tools_generate_comments',
						ai_model: $('#ai-model').val(),
						model_group: $('#ai-model').find('option:selected').parent().attr('label'),
						post_id: '<?php echo $post->ID; ?>',
						count: $('#comment-counts').val(),
						start_date: $('#start-date').val(),
						end_date: $('#end-date').val(),
						keywords: $('#keywords').val(),
						languages: $('#languages').val(),
						_wpnonce: '<?php echo wp_create_nonce('openai_tools_generate_comments'); ?>'
					},
					success: function(response) {
						console.log(response);
						$('.ai-commenter__loading').hide();

						$('.ai-commenter__generated_comments').html('');
						$.each(response.data, function(index, value) {
							//show checkbox and commenter field,  date field and comment textarea field		
							$('.ai-commenter__generated_comments').append(`
							<div class="ai-commenter__generated_comments__item">
								<div class="ai-commenter__generated_comments__item__content">
									<div class="ai-commenter__generated_comments__item__content__checkbox">
										<input type="checkbox" name="comment-item" value="` + value.id + `">
									</div>
									<div class="ai-commenter__generated_comments__item__content__detail">
										<div class="ai-commenter__generated_comments__item__content__info">
											<input type="text" name="commenter" value="` + value.commenter + `" placeholder="commenter">
											<input type="date" name="date" value="` + value.date + `" placeholder="Date">
										</div>
										<div class="ai-commenter__generated_comments__item__content__comment">
											<textarea name="comment" rows="4" placeholder="comment">` + value.detail + `</textarea>
										</div>
									</div>
								</div>
							</div>
						`);
						});

						//add select all button
						$('.ai-commenter__generated_comments').prepend(`
						<div class="ai-commenter__generated_comments__item">
							<input type="checkbox" name="comment-item" id="select-all">
							<label for="select-all">Select all</label>
						</div>
					`);


						//select all checkbox
						$('#select-all').click(function() {
							if ($(this).is(':checked')) {
								$('input[name="comment-item"]').prop('checked', true);
								$('.ai-commenter__add_comments').addClass('show');
							} else {
								$('input[name="comment-item"]').prop('checked', false);
								$('.ai-commenter__add_comments').removeClass('show');
							}
						});

						//show add comment button when at least one checkbox is checked
						$('input[name="comment-item"]').click(function() {
							if ($('input[name="comment-item"]:checked').length > 0) {
								$('.ai-commenter__add_comments').addClass('show');
							} else {
								$('.ai-commenter__add_comments').removeClass('show');
							}
						});

						$('#add-comment').off('click');
						//click on add comment button and ajax add comments
						$('#add-comment').click(function() {
							$('.ai-commenter__add_loading').show();

							$comments = [];

							$('input[name="comment-item"]:checked').each(function() {
								$comments.push({
									id: $(this).val(),
									commenter: $(this).parent().parent().find('input[name="commenter"]').val(),
									date: $(this).parent().parent().find('input[name="date"]').val(),
									comment: $(this).parent().parent().find('textarea[name="comment"]').val()
								});
							});

							if ($comments.length == 0) {
								alert('Please select at least one comment');
								$('.ai-commenter__add_loading').hide();
								return;
							}
							$.ajax({
								url: '<?php echo admin_url('admin-ajax.php'); ?>',
								type: 'POST',
								data: {
									action: 'openai_tools_add_comments',
									post_id: '<?php echo $post->ID; ?>',
									comments: $comments,
									_wpnonce: '<?php echo wp_create_nonce('openai_tools_add_comments'); ?>'
								},
								success: function(response) {
									$('.ai-commenter__add_loading').hide();

									$('.ai-commenter__add_comments').append('\
									<div class="ai-commenter__add_comments__success" style="color: green;">Add Done!</div>\
								');
									setTimeout(function() {
										$('.ai-commenter__add_comments__success').remove();
									}, 3000);
								}
							});
						});
					}
				});
			});
		});
	<?php endif; ?>
</script>