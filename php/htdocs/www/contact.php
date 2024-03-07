<?php
include_once('header.php');
include_once('navbar.php');
?>

<div id="container-contact" class="container">
	<div class="section bg-color-black text-color-white">
		<div class="section-control">
			<div>
				<p>CONTACT US</p>
				<p>
					<p>บริษัท โมเซิร์ฟ จำกัด</p>
					<p>เลขที่ 1000/85 อาคารลิเบอรตี้ พลาซ่า ชั้น 3 ถ.สุขุมวิท 55</p>
					<p>แขวงคลองตันเหนือ เขตวัฒนา จ.กรุงเทพมหานคร 10110</p>
					<p>โทร. 02-714-7596</p>
				</p>
			</div>
			<div>
				<div>
					<p>สอบถามข้อมูลเพิ่มเติมได้ที่</p>
					<div class="grid-container-contact">
						<!-- <img class="item-contact-facebook" src="<?php echo $configs['path-image']; ?>/icons/facebook-white.png" />
						<img class="item-contact-ig" src="<?php echo $configs['path-image']; ?>/icons/instagram-white.png" />
						<img class="item-contact-line" src="<?php echo $configs['path-image']; ?>/icons/line-white.png" />
						<img class="item-contact-qrcode" src="<?php echo $configs['path-image']; ?>/contents/qr-code.png" /> -->
						<a class="item-contact-facebook" href="<?php echo $market['facebook']; ?>" target="_blank">
							<img src="<?php echo $configs['path-image']; ?>/icons/facebook-white.png" />
						</a>
						<a class="item-contact-ig" href="<?php echo $market['ig']; ?>" target="_blank">
							<img src="<?php echo $configs['path-image']; ?>/icons/instagram-white.png" />
						</a>
						<a class="item-contact-line" href="<?php echo $market['line-oa']; ?>" target="_blank">
							<img src="<?php echo $configs['path-image']; ?>/icons/line-white.png" />
						</a>
						<img class="item-contact-qrcode" src="<?php echo $configs['path-image']; ?>/contents/line-oa-qrcode-black.png" />
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- <div class="section-map bg-color-white">
		<p>API Google MAP</p>	
	</div> -->
</div>

<?php
include_once('footer.php');
