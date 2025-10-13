new fullpage('#homeFullpage', {
	anchors: ["", "product", "news", "contact"], // 页面锚点
	navigation: true, // 是否显示导航条
	navigationPosition: "left", // 导航条位置
	paddingTop: "0", // 顶部导航条占位高度
	paddingBottom: "0", // 底部导航条占位高度
	fitToSection: true, // 是否适应每个部分的高度
	afterLoad: function (origin, destination, direction) {
		// 获取目标元素
		var navbarBackground = document.getElementById('navbarBackground');

		// 如果不是第一屏幕，添加类 'navbar-background-fff'
		if (destination.index !== 0) {
			navbarBackground.classList.add('navbar-background-fff');
		} else {
			// 如果是第一屏幕，移除类 'navbar-background-fff'
			navbarBackground.classList.remove('navbar-background-fff');
		}
	}
});

const swiper = new Swiper('.swiper', {
	centeredSlides: true,
	effect: 'fade',
	autoplay: {
		enabled: true,
		delay: 6000,
		disableOnInteraction: false,
	},
	loop: true,
	pagination: {
		el: '.swiper-pagination',
		type: 'bullets',
		dynamicBullets: true,
	},
	navigation: {
		nextEl: '.swiper-button-next',
		prevEl: '.swiper-button-prev',
	},
});

/*document.addEventListener("DOMContentLoaded", function () {
 const swiperOverlay = document.querySelector('.swiper-overlay');
 const overlayCard = document.querySelector('.swiper-overlay_card');
 const switchBtn = document.querySelector('.swiper-overlay_switch');
 
 const contentHeight = overlayCard.scrollHeight; // 获取内容的实际高度
 overlayCard.style.height = `${contentHeight}px`; // 初始状态下设置为内容的实际高度
 
 switchBtn.addEventListener('click', function () {
 swiperOverlay.classList.toggle('close');
 if (!swiperOverlay.classList.contains('close')) {
 overlayCard.style.height = `${contentHeight}px`; // 展开时将高度设置为内容的实际高度
 } else {
 overlayCard.style.height = '0'; // 关闭时将高度设置为 0
 }
 });
 });*/
window.onload = function () {
	// 定义一些现代化的 UI 安全色
	const colors = ['#F44336', // 红色
		'#E91E63', // 粉红色
		'#9C27B0', // 紫色
		'#673AB7', // 深紫色
		'#3F51B5', // 靛蓝色
		'#2196F3', // 蓝色
		'#03A9F4', // 浅蓝色
		'#00BCD4', // 青色
		'#009688', // 水鸭色
		'#4CAF50', // 绿色
		'#8BC34A', // 浅绿色
		'#CDDC39', // 青柠色
		'#FFEB3B', // 黄色
		'#FFC107', // 琥珀色
		'#FF9800', // 橙色
		'#FF5722'  // 深橙色
	];

	// 获取所有带有 uk-card-footer 类的元素
	const footers = document.querySelectorAll('.uk-card-footer');

	// 检查颜色数量是否足够
	if (footers.length > colors.length) {
		console.error('颜色数量不足以分配给所有元素');
		return;
	}

	// 随机打乱颜色数组
	const shuffledColors = colors.sort(() => 0.5 - Math.random());

	// 遍历每个元素并设置唯一背景颜色
	footers.forEach((footer, index) => {
		footer.style.backgroundColor = shuffledColors[index];
	});
};
