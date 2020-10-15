const topNav = document.getElementById("top-nav");
const sideNav = document.getElementById("side-nav");
const topNavInitOffset = topNav.offsetTop;

window.addEventListener("scroll", () => sticky());

function sticky() {
    if(window.pageYOffset > topNavInitOffset) {
        sideNav.classList.add("sticky-custom");
        topNav.classList.add("sticky-top");
    } else {
        sideNav.classList.remove("sticky-custom");
        topNav.classList.remove("sticky-top");
    }
}