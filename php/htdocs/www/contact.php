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

	<div class="section-map bg-color-white">
		<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15502.43078016927!2d100.5855775!3d13.7421852!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29ee781d37df5%3A0xaaa4c8a8956b0053!2z4Lia4Lij4Li04Lip4Lix4LiXIOC5guC4oeC5gOC4i-C4tOC4o-C5jOC4nyDguIjguLPguIHguLHguJQ!5e0!3m2!1sen!2sth!4v1709874788273!5m2!1sen!2sth" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>	
	</div>
</div>

<?php
include_once('footer.php');
