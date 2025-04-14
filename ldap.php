<div class="ratio ratio-16x9">
                            <video id="bestiaryVideo" class="object-fit-cover lazy" poster="{{ asset('img/bestiary.jpg') }}" autoplay="autoplay" muted="muted" preload="auto" loop="loop" preload="yes" playsinline="playsinline" tabindex="0"></video>
                        </div>

<script>
        function updateVideoSource() {
    const video = document.getElementById('bestiaryVideo');
    const desktopSrc = "{{ asset('https://cdn.moscow-bestiary.ru/videos/bestiary.mp4') }}";
    const mobileSrc = "{{ asset('https://cdn.moscow-bestiary.ru/videos/bestiary-mob.mp4') }}";
    var ratioClass = document.querySelector('.ratio');
    
    // Удаляем существующие <source> элементы
    video.innerHTML = '';

    if (window.innerWidth > 576) {
        const source = document.createElement('source');
        source.dataset.src = desktopSrc;
        source.type = 'video/mp4';
        video.appendChild(source);
        ratioClass.classList.remove('ratio-1x1');
        ratioClass.classList.add('ratio-16x9');
    } else {
        const source = document.createElement('source');
        source.dataset.src = mobileSrc;
        source.type = 'video/mp4';
        video.appendChild(source);
        ratioClass.classList.remove('ratio-16x9');
        ratioClass.classList.add('ratio-1x1');
    }
    
    // Перезагружаем видео, чтобы применить новый источник
    video.load();
}

function setupLazyLoading() {
    var lazyVideos = [].slice.call(document.querySelectorAll("video.lazy"));
    if ("IntersectionObserver" in window) {
        var lazyVideoObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (video) {
                if (video.isIntersecting) {
                    for (var source of video.target.children) {
                        if (typeof source.tagName === "string" && source.tagName === "SOURCE") {
                            source.src = source.dataset.src;
                        }
                    }
                    video.target.load();
                    video.target.classList.remove("lazy");
                    lazyVideoObserver.unobserve(video.target);
                }
            });
        });

        lazyVideos.forEach(function (lazyVideo) {
            lazyVideoObserver.observe(lazyVideo);
        });
    }
}

window.addEventListener('resize', updateVideoSource);
document.addEventListener('DOMContentLoaded', function () {
    updateVideoSource(); // Устанавливаем источник при загрузке страницы
    setupLazyLoading();  // Настройка ленивой загрузки
});
    </script>
