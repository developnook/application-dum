		<div id="<?php echo $boxName; ?>-box" class="page-box" style="<?php echo ($boxVisible)? '': 'display: none;'; ?>">
			<div id="<?php echo $boxName; ?>-toolbar" class="page-toolbar">
				<table border="0" cellspacing="0" cellpadding="0">
				<tbody>
				<tr>
					<td id="<?php echo $boxName; ?>-toolbar-left" class="page-toolbar-left" align="left" valign="middle">
						<?php
							if (Page::$page->getBoxCount() == 1) {
						?>
						<div id="setting" class="left-toolbar-button" onclick="_page.slider.execute();">
							<div />
							<div />
							<div />
						</div>
						<?php
							}
							else {
						?>
							<div id="goback" class="left-toolbar-button" onclick="_page.back();">
								<div />
							</div>
						<?php
							}
						?>
					</td>
					<td id="<?php echo $boxName; ?>-toolbar-center" class="page-toolbar-center" align="center" valign="middle" onclick="_page.toolbarSwitch();">
						<div id="logo" class="logo">
							<?php include('com/moserv/console/gumnan/logo.php'); ?>
						</div>
					</td>
					<td id="<?php echo $boxName; ?>-toolbar-right" class="page-toolbar-right" valign="middle">
						<?php
							if (Page::$page->getBoxCount() == 1) {
						?>
						<div id="signout" class="right-toolbar-button" onclick="_page.signout();"></div>
						<?php
							}
						?>
					</td>
				</tr>
				</tbody>
				</table>
			</div>

			<div id="<?php echo $boxName; ?>-layout" class="page-layout">
