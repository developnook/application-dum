document.addEventListener('DOMContentLoaded', (event) => {

	let menu = document.querySelectorAll('ul#menu > li');
	menu.forEach(element => {
		element.addEventListener('click', (event) => {
			let page = element.dataset.value;
			// alert(`${element.dataset.value}`)
			document.location.href=`${page}`;
		})
	});
});

function navToggle() {
	var element = document.getElementById("navTrigger");
	element.classList.toggle("active");

	var navshow = document.getElementById("nav-res-active");
	navshow.classList.toggle("show");
}