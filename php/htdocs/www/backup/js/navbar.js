document.addEventListener('DOMContentLoaded', event => {

	let menu = document.querySelectorAll('ul#menu > li');

	menu.forEach(element => {

		element.addEventListener('click', (event) => {

			document.location.href=`${element.dataset.value}`;
		})
	});
});