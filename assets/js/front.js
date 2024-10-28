
document.addEventListener('avalon23-start-redraw-page', (e) => {
    var elem = document.querySelector(".avalon23_loader_wrapper");
    elem.style.display = "block";
});

document.addEventListener('avalon23-end-redraw-page', (e) => {
    var elem = document.querySelector(".avalon23_loader_wrapper");
    elem.style.display = "none";
});