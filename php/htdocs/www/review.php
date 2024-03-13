<?php
include_once('header.php');
include_once('navbar.php');
?>

<div id="container-review" class="container">
	<section class="section-content bg-color-black text-color-white">
		<div class="content">
			<div class="img-section d-lg-none d-sm-block">
				<img src="<?php echo $configs['path-image']; ?>/contents/review/review-picture-1.png" />
			</div>
			<div class="text-section">
				<div class="text-box text-left d-sm-text-center">
					<p class="title ma-0">REVIEW</p>
					<p class="title">คุณแอมมี่</p>
					<p class="detail">ใช้แล้วสวย เชื่อว่าหน้าสวยๆ ต้องมาพร้อมฟันสวยๆ </p>
					<p class="detail">ติดใจตั้งแต่ครั้งแรกที่ใช้ หลอดเดียวจบทุกอย่างจริงๆ</p>
					<p class="detail mb-20px">อยากยิ้มมั่นใจ ไม่อยากกังวลเรื่องช่องปากไว้ใจ ดำยาสีฟันชาร์โคล</p>
					<a href="<?php echo $path['charcoal']; ?>">
						<button class="btn btn-black">อ่านต่อ</button>
					</a>
				</div>
			</div>
			<div class="img-section d-sm-none">
				<img src="<?php echo $configs['path-image']; ?>/contents/review/review-picture-1.png" />
			</div>
		</div>
	</section>

	<section class="section-content bg-color-white text-color-black">
		<div class="content">
			<div class="img-section">
				<img src="<?php echo $configs['path-image']; ?>/contents/review/review-picture-2.png" />
			</div>
			<div class="text-section">
				<div class="text-box text-left d-sm-text-center">
					<p class="title">คุณขิม</p>
					<p class="detail">ดำใช้ดีจริง หลอดเดียวจบทุกปัญหา ไร้คราบหินปูน</p>
					<p class="detail mb-20px">น้องขิมไว้ใจดำค่ะ เปลี่ยนมาใช้ ดำยาสีฟันชาร์โคล กันนะคะ</p>
					<a href="<?php echo $path['charcoal']; ?>">
						<button class="btn btn-black">อ่านต่อ</button>
					</a>
				</div>
			</div>
		</div>
	</section>

	<section class="section-content bg-color-black text-color-white">
		<div class="content">
			<div class="img-section d-lg-none d-sm-block">
				<img src="<?php echo $configs['path-image']; ?>/contents/review/review-picture-3.png" />
			</div>
			<div class="text-section">
				<div class="text-box text-left d-sm-text-center">
					<p class="title">คุณหมิว</p>
					<p class="detail">เห็นผลตั้งแต่ครั้งแรกที่ใช้ จากหมิวเป็นคนที่ไม่กล้ายิ้ม</p>
					<p class="detail">ตอนนี้กล้ายิ้มได้สุดปากเลยค่า ดำยาสีฟันชาร์โคล</p>
					<p class="detail mb-20px">เรียกความมั่นใจหมิวกลับมาได้จริงๆค่ะ</p>
					<a href="<?php echo $path['charcoal']; ?>">
						<button class="btn btn-black">อ่านต่อ</button>
					</a>
				</div>
			</div>
			<div class="img-section d-sm-none">
				<img src="<?php echo $configs['path-image']; ?>/contents/review/review-picture-3.png" />
			</div>
		</div>
	</section>
</div>

<?php
include_once('footer.php');
