<?php
	require_once('com/moserv/ttt/config.php');
?>
	<div class="toolbar">
		<div class="toolbar-table">
			<div class="toolbar-row">
				<div class="toolbar-cell">
					<div style="display: table-cell; background-image: url(/image/bank-krung-sri-24.png); width: 70px; height: 24px; border: 3px solid <?php echo $colors['dark-brown']; ?>;"></div>
				</div>
				<div class="toolbar-cell" style="text-align: right; vertical-align: middle;">
					<div style="display: table; margin: auto 0px auto auto; vertical-align: middle;">
						<div style="display: table-cell; vertical-align: middle; padding-right: 20px;"><div style="color: #ffc;">Username / ผู้ใฃ้งานระบบ: <span id="edtUsername"><?php echo $_SESSION['username']; ?></span></div></div>
						<div style="display: table-cell; vertical-align: middle; padding-right: 20px;"><a href="/main/home.php">Home / หน้าหลัก</a></div>
						<div style="display: table-cell; vertical-align: middle;"><a href="/main/exit.php">Logout / ออกจากระบบ</a></div>
					</div>
				</div>
			</div>
		</div>
	</div>

