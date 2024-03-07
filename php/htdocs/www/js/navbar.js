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
	let element = document.getElementById("navTrigger");
	element.classList.toggle("active");
}