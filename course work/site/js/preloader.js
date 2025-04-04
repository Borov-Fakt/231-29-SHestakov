window.addEventListener('load', function() {
    let preloader =this.document.getElementById('preloader');
    preloader.classList.add('loding__block');
    this.setInterval(function(){
        preloader.classList.add('progress');
    }, 800);
},);
