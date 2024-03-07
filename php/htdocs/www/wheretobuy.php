<?php
include_once('header.php');
include_once('navbar.php');
?>

<div id="container-wheretobuy" class="container">
	<div class="section bg-color-black text-color-white">
		<div>
			<div>
				<p>WHERE TO BUY</p>
				<p>
					Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper sus- cipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at Lorem ipsum dolor sit amet, consec-
				</p>
			</div>
			<div>
				<a target="_blank" href="<?php echo $market['line-oa']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/line-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['line-oa']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/line-my-shop-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['facebook']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/facebook-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['ig']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/instagram-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['shopee']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/shopee-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['lazada']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/lazada-white.png" />
				</a>
				<a target="_blank" href="<?php echo $market['tiktok']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/tiktok-01.png" />
				</a>
			</div>
			<div>
				<img src="<?php echo $configs['path-image']; ?>/contents/where-to-buy/where-to-buy-picture.png" />
			</div>
		</div>
	</div>
</div>

<?php
include_once('footer.php');
