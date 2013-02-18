<?php
/* Copyright (C) 2010-2013	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013	Herve Prot		<herve.prot@symeos.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

	<!-- Footer -->
	<script src="theme/symeos/js/setup.js"></script>

	<script src="theme/symeos/js/developr.navigable.js"></script>
	<script src="theme/symeos/js/developr.scroll.js"></script>

	<script src="theme/symeos/js/s_scripts.js"></script>
	<!-- <script src="theme/symeos/js/symeos.js"></script> -->

	<script src="theme/symeos/js/developr.input.js"></script>
	<script src="theme/symeos/js/developr.message.js"></script>
	<script src="theme/symeos/js/developr.modal.js"></script>
	<script src="theme/symeos/js/developr.notify.js"></script>
	<script src="theme/symeos/js/developr.progress-slider.js"></script>
	<script src="theme/symeos/js/developr.tooltip.js"></script>
	<script src="theme/symeos/js/developr.confirm.js"></script>
	<script src="theme/symeos/js/developr.agenda.js"></script>

	<script src="theme/symeos/js/developr.tabs.js"></script>
	<!-- Must be loaded last -->

	<!-- Tinycon -->
	<script src="includes/js/tinycon.min.js"></script>

	<script>
		// Call template init (optional, but faster if called manually)
		$.template.init();

		// Favicon count
		Tinycon.setBubble(<?php echo $count_icon; ?>);

		// box actions (show/hide, remove)
		prth_box_actions = {
			init: function() {
				$('.box_actions').each(function(){
					$(this).append('<span class="bAct_hide"><img src="theme/blank.gif" class="bAct_x" alt="" /></span>');
					$(this).append('<span class="bAct_toggle"><img src="theme/blank.gif" class="bAct_minus" alt="" /></span>');
					$(this).find('.bAct_hide').on('click', function(){
						$(this).closest('.box_c').fadeOut('slow',function(){
							$(this).remove();
							prth_stickyFooter.resize();
						});
					});
					$(this).find('.bAct_toggle').on('click', function(){
						if( $(this).closest('.box_c_heading').next('.box_c_content').is(':visible') ) {
							$(this).closest('.box_c_heading').next('.box_c_content').slideUp('slow',function(){
								prth_stickyFooter.resize();
							});
							$(this).html('<img src="theme/blank.gif" class="bAct_plus" alt="" />');
						} else {
							$(this).closest('.box_c_heading').next('.box_c_content').slideDown('slow',function(){
								prth_stickyFooter.resize();
							});
							$(this).html('<img src="theme/blank.gif" class="bAct_minus" alt="" />');
						}
					});
				});
			}
		};

		// Resize the footer
		prth_stickyFooter = {
				init: function() {
					prth_stickyFooter.resize();
				},
				resize: function() {
					if($("#sticky-footer-push").height() === undefined)
						var docHeight = $(document.body).height();
					else
						var docHeight = $(document.body).height() - $("#sticky-footer-push").height();

					if(docHeight < $(window).height()){
						var diff = $(window).height() - docHeight +1;
						if ($("#sticky-footer-push").length == 0) {
							$('#footer').before('<div id="sticky-footer-push"></div>');
						}
						$("#sticky-footer-push").height(diff - $("#title-bar").height() - 2);
					} else {
						$("#sticky-footer-push").remove();
					}
				}
			};
	</script>

	<footer id="footer">
		<div class="with-mid-padding">
			<div>Copyright &copy; 2012-2013
			speedealing.com - under GPLv3 license
			</div>
		</div>
	</footer>
