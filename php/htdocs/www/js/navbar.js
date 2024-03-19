document.addEventListener('DOMContentLoaded', (event) => {

	let menu = document.querySelectorAll('.navbar-menu > li');
	menu.forEach(element => {
		element.addEventListener('click', (event) => {
			let page = element.dataset.value;
			document.location.href=`${page}`;
		})
	});

	document.getElementById('navTrigger').addEventListener('click', (event) => {
		let navtriger = document.getElementById("navTrigger");
		navtriger.classList.toggle('active');
		document.getElementById("nav-res-active").classList.toggle('show');
	});

});

// function navToggle() {
// 	var element = document.getElementById("navTrigger");
// 	element.classList.toggle("active");

// 	var navshow = document.getElementById("nav-res-active");
// 	navshow.classList.toggle("show");
// }