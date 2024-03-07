<?php
	require_once('com/moserv/ttt/config.php');
?>
	<div class="panel">
		<div class="panel-table">
			<div class="panel-row">
				<div class="panel-cell">
					<div class="panel-toolbar-table">
						<div class="panel-toolbar-row">
							<div class="panel-toolbar-cell">
								<div
									class="panel-toolbar-image"
									style="background-image: url(/image/bank-krung-sri-24.png);"
								/>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-cell">
					<div class="panel-toolbar-table" style="margin: 0px 0px 0px auto;">
						<div class="panel-toolbar-row">
							<div class="panel-toolbar-cell">Username / ผู้ใฃ้งานระบบ: <span id="edtUsername"><?php echo $_SESSION['username']; ?></span></div>
							<div class="panel-toolbar-cell"><a href="/main/home.php">Home / หน้าหลัก</a></div>
							<div class="panel-toolbar-cell"><a href="/main/exit.php">Logout / ออกจากระบบ</a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

