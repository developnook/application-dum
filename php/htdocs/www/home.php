<?php
include_once('header.php');
include_once('navbar.php');
?>

<div id="container-home" class="container">
	<div class="section-content bg-color-black text-color-white">
		<div class="container-custom">
			<div class="content">
				<div class="product-img-responsive">
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-picture-1-1.png" />
				</div>
				<div class="text-content">
					<p class="title">ยิ่งพูดยิ่งมั่นใจ ใครใช้ก็รู้สึกดี</p>
					<p class="detail">DUM เชื่อว่า ทุกคนสามารถมีสุขภาพช่องปากและฟันที่ดี</p>
				</div>
				<div class="product-img">
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-picture-1-1.png" />
				</div>
			</div>
		</div>
	</div>
	<div class="section-content bg-color-black text-color-white">
		<div class="container-custom">
			<div class="content">
				<div class="product-img-responsive">
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-picture-1-1.png" />
				</div>
				<div class="text-content">
					<p class="title">ยาสีฟันชาร์โคล</p>
					<p class="detail">
						สูตรสมุนไพรสกัดเข้มข้น ด้วยส่วนผสมสมุนไพรจากธรรมชาติและกรรมวิธีการผลิตที่ทันสมัย สามารถคงคุณค่าสมุนไพรไว้อย่างครบถ้วน ช่วยขจัดกลิ่นปาก ลดอาการเสียวฟันเพื่อปากสะอาด สุขภาพเหงือกและฟันแข็งแรง ยาวนานตลอดวัน
					</p>
					<div class="mt-30px">test icon</div>
					<p class="detail">ใช้ยาสีฟันประมาณเท่าเมล็ดถั่วเขียว มี 3 ขนาดให้เลือก</p>
				</div>
				<div class="product-img">
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-picture-1-1.png" />
				</div>
				<div class="text-content">
					<p class="title-small">ส่วนประกอบสําคัญ</p>
					<p class="detail">Guava Leaf Aloe vera Charcoal Powder</p>
				</div>
			</div>
		</div>
	</div>
	<!-- <div class="section bg-color-black text-color-white">
		<div class="section-img">
			<img src="<?php echo $configs['path-image']; ?>/contents/home/home-picture-1-1.png" />
		</div>	
		<div class="section-control">
			<div>
				<p>ยาสีฟันชาร์โคล</p>
				<p>
					สูตรสมุนไพรสลัดเข้มข้น ด้ยส่วนผสมสมุนไพรจากธรรมชาติและกรรมวิธีการผลิตที่ทันสมัย สามารถคงคุณค่าสมุนไพรไว้อย่างครบถ้วน ช่วยขจัดกลิ่นปาก ลดอาการเสียวฟันเพื่อปากสะอาด สุขภาพเหงือกและฟันแข็งแรง ยาวนานตลอดวัน
				</p>
				<p>
					icon
				</p>
				<p>
					ใช้ยาสีฟันประมาณเท่าเมล็ดถั่วเขียว มี 3 ขนาดให้เลือก
				</p>
			</div>
			<div></div>
			<div>
				<p>ส่วนประกอบสำคัญ</p>
				<p>Guava Leaf    Aloe vera   Charcoal Powder</p>
			</div>
		</div>
	</div> -->
	<div class="section bg-color-white text-color-black">
		<div class="section-img">
			<img src="<?php echo $configs['path-image']; ?>/contents/knowledge/knowledge-picture-1.png" />
		</div>	
		<div class="section-control">
			<div></div>
			<div></div>
			<div>
				<p>ชาร์โคลดียังไง?</p>
				<p>
					สูตรสมุนไพรสกัดเข้มข้น ด้วยส่วนผสมสมุนไพรจากธรรมชาติ
					และกรรมวิธีการผลิตที่ทันสมัย สามารถคงคุณค่าสมุนไพร
				</p>
				<div>
					<a class="btn btn-black" href="<?php echo $path['knowledge']; ?>" >อ่านต่อ</a>
				</div>
			</div>
		</div>
	</div>
	<div class="section bg-color-white text-color-black">
		<div>
			<div><p>REVIEW</p></div>
			<div>
				<div>
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-3-1.jpg" />
				</div>
				<div>
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-3-2.jpg" />
				</div>
				<div>
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-3-3.jpg" />
				</div>
				<div>
					<img src="<?php echo $configs['path-image']; ?>/contents/home/home-3-4.jpg" />
				</div>
			</div>
		</div>
	</div>
	<div class="section bg-color-white text-color-black">
		<div>
			<div><p>WHERE TO BUY</p></div>
			<div>
				<p>
			Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet
dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper sus-
cipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate
velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at Lorem ipsum dolor sit amet, consec-
				</p>
			</div>
			<div>
				<a target="_blank" href="<?php echo $market['line-oa']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/line-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['line-oa']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/line-my-shop-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['facebook']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/facebook-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['ig']; ?>">
					<img src="<?php echo $configs['path-image']; ?>/icons/instagram-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['shopee']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/shopee-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['lazada']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/lazada-black.png" />
				</a>
				<a target="_blank" href="<?php echo $market['tiktok']; ?>" >
					<img src="<?php echo $configs['path-image']; ?>/icons/tiktok-01.png" />
				</a>
			</div>
		</div>
	</div>
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
