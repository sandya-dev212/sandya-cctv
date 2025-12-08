<?php /* Views/dashboard/index.php */ ?>
<div id="pageHead" class="flex flex-col gap-3">
  
  <div class="w-full flex flex-col gap-3 items-start">
    <p class="text-3xl text-white font-bold">Dashboard</p>
    <div class="flex flex-row gap-3">
      
      <?php foreach($dashAccess as $dash):?>
        <a href="/dashboard?id=<?= $dash['id'] ?>" class="text-white hover:cursor-pointer p-2 rounded-md <?= $curDashId == $dash['id'] ? 'bg-slate-400' : 'bg-slate-600' ?>"> <?= $dash['name']?> </a>
      <?php endforeach;?>
    </div>

    <?php if(session()->getFlashdata('message')): ?>
        <div class="alert alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('message') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
  </div>

  <div class="w-full flex justify-between max-[850px]:flex-col">
    
    <form method="get" action="/dashboard?id=<?= $curDashId ?>" id="flt" class="flex flex-col gap-3 mb-10">
      <input type="hidden" name="id" value="<?= (int)($dash['id'] ?? 0) ?>" >
      <div class="flex gap-3 max-[850px]:flex-col">
        <input type="text" name="q" value="<?= esc($q ?? '') ?>" placeholder="Cari alias/NVR/monitor..." style="min-width:240px" class="bg-slate-800 p-2 rounded-md">
        <input type="hidden" name="page" value="<?= (int)($page ?? 1) ?>" >
        <div class="flex flex-row gap-3">
          <button class="btn rounded-md bg-blue-500 hover:bg-blue-400 hover:cursor-pointer max-[850px]:w-full" type="submit">Search</button>
          <a class="btn text-center" href="/dashboard?id=0" style="background:#ef4444">Reset</a>
        </div>
      </div>
      
      <div class="w-full flex flex-row items-center gap-3">
        <label for="per">Per page</label>
        <select onchange="this.form.submit()" name="per" id="per" class="bg-slate-800 rounded-md p-2">
          <?php foreach ([6, 12, 24, 46, 100] as $opt): ?>
            <option value="<?= $opt ?>" <?= (isset($per) && (int)$per === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <div class="flex flex-row items-center gap-3 mb-5">
      <button type="button" id="btnSlide" class="btn hover:cursor-pointer max-[850px]:w-full" style="background:#7c3aed">Slideshow Cameras</button>
      <select id="slideMsSel" title="Interval slideshow (detik)" style="background:#111827;border:1px solid #1f2937;color:#e5e7eb;border-radius:10px;padding:8px">
        <?php foreach ([5,10,15,30,60,120,300] as $s): ?>
          <option value="<?= $s ?>"><?= $s ?>s</option>
        <?php endforeach; ?>
      </select>
    </div>

  </div>
</div>

<?php if (empty($tiles)): ?>
  <p style="color:#94a3b8">Belum ada kamera untuk ditampilkan.</p>
<?php else: ?>

  <div id="mainDiv" class="pb-10 relative">
    
    <div id="mainGrid" class="grid-stack">
      <?php foreach ($tiles as $t): ?>
        <div class="grid-stack-item" 
          gs-x="<?= esc($t['size'])['x'] ?>"
          gs-y="<?= esc($t['size'])['y'] ?>" 
          gs-w="4" 
          gs-h="4"
        >
          <div class="cam h-full w-full"
            data-id="<?= esc($t['id']) ?>"
            data-hls="<?= esc($t['hls']) ?>"
            data-alias="<?= esc($t['alias']) ?>"
            data-nvr-id="<?= $t['nvr_id'] ?? 0 ?>"
            data-mon="<?= esc($t['monitor_id']) ?>"
          >
            <div class="thumb h-full relative p-0.5" id="<?= esc($t['id']) ?>">

              <video id="my-video" class="vid object-cover rounded-md bg-black" muted playsinline autoplay></video>
  
              <button class="btn ghost p-3 absolute top-2 right-2 z-1 hover:cursor-pointer hover:bg-slate-400" onclick="fsTile(event,this,null)" title="Fullscreen">⤢</button>
  
              <button 
                onclick="getCameraVideo('<?= $t['nvr_id'] ?? 0 ?>', '<?= esc($t['monitor_id']) ?>', '<?= esc($t['id']) ?>')" 
                title="Click to access the video" 
                class="absolute left-2 top-2 bg-slate-900/50 border border-slate-200 rounded-md px-2 py-1 text-[10px] font-bold hover:cursor-pointer hover:bg-violet-900/50"
              >
                <?= esc($t['nvr']) ?> / <?= esc($t['monitor_id']) ?>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div id="pager" class="flex justify-center gap-1.5 my-6">
      <?php
        $curr = $page ?? 1;
        $max  = $pages ?? 1;
        $perQ = $per ?? 10;
        $qStr = ($q ?? '') !== '' ? '&q=' . urlencode($q) : '';
        $window = 1; $start = max(1, $curr-$window); $end = min($max, $curr+$window);
      ?>

      <a class="btn ghost" href="<?= '/dashboard?id=' . $curDashId . '&page='. 1 .'&per='.$perQ.$qStr ?>">Start</a>
  
      <?php if ($curr > 1): ?>
        <a class="btn ghost" href="<?= '/dashboard?id=' . $curDashId . '&page='. $curr - 1 .'&per='.$perQ.$qStr ?>">&laquo;</a>
      <?php else: ?>
        <span class="btn ghost" style="opacity:.5;pointer-events:none">&laquo;</span>
      <?php endif; ?>
  
      <?php for ($i=$start; $i<=$end; $i++): ?>
        <?php if ($i === $curr): ?>
          <span class="btn" style="pointer-events:none"><?= $i ?></span>
        <?php else: ?>
          <a class="btn ghost" href="<?= '/dashboard?id=' . $curDashId . '&page='. $i .'&per='.$perQ.$qStr ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
  
      <?php if ($curr < $max): ?>
        <a class="btn ghost" href="<?= '/dashboard?id=' . $curDashId . '&page='. $curr + 1 .'&per='.$perQ.$qStr ?>">&raquo;</a>
      <?php else: ?>
        <span class="btn ghost" style="opacity:.5;pointer-events:none">&raquo;</span>
      <?php endif; ?>

      <a class="btn ghost" href="<?= '/dashboard?id=' . $curDashId . '&page='. $max .'&per='.$perQ.$qStr ?>">End</a>

    </div>

    <!-- Modal for make selected grid camera 'full screen' (this component will be used when user select one of the camera grids in the dashboard) -->
    <div id="modalSlide" class="bg-black fixed hidden inset-0 z-99 items-center justify-center">
      <div id="modalSlideBody" class="relative w-[85%]"></div>
    </div>
  </div>

  <!-- Slideshow component -->
  <div id="mainSlideDiv" class="hidden mainSlideDiv relative">

    <!-- SlideDiv as the div that the grid of cameras will be placed -->
    <div id="slideDiv"></div>

    <!-- Slider navigation -->
    <div id="slideNav" class="fixed bottom-0 w-full flex justify-center items-center">
      <div class="flex flex-row w-max gap-[1vw] justify-between items-center py-10 px-10">
        <div id="prevBtn" class="bg-black w-8 h-8 rounded-full flex items-center justify-center font-bold hover:cursor-pointer border border-slate-500"><</div>
        <button id="pauseBtn" onclick="togglePlay()" class="bg-black px-10 py-3 rounded-full flex items-center justify-center font-bold hover:cursor-pointer border border-slate-500">Pause</button>
        <div id="nextBtn" class="bg-black w-8 h-8 rounded-full flex items-center justify-center font-bold hover:cursor-pointer border border-slate-500">></div>
      </div>
    </div>

    <!-- Modal for make selected grid camera 'full screen' (this component will be used when the user is in slideshow mode ) -->
    <div id="modalSlideFull" class="bg-black fixed hidden top-0 left-0 right-0 bottom-0 z-99 items-center justify-center">
      <div id="modalSlideFullBody" class="relative w-[85%]"></div>
    </div>

  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.6.13/hls.min.js"></script>

<?php if(session()->getFlashdata('message')): ?>
  <script>
      $(document).ready(function() {
          let message = "<?= session()->getFlashdata('message') ?>";
          alert(message);
      });
  </script>
<?php endif; ?>

<script>
  let start = 0;
  const batchSize = 6;

  let grid;
  let sliderInterval;
  let isModalOpened = false;
  let isModalFullOpened = false;
  let isPlay = true; 
  let customCellHeight = '10vh';
  let arrayCamera = [];
  const currentViewportWidth = window.innerWidth;
  
  // Function to render camera grid inside the slideshow mode
  function renderSlide() {

    grid.removeAll();

    // Slice the arrayCamera to make a 'page'
    // So, index 0 to 6 (items 0 to 6) will be the first page and so on
    const sliced = arrayCamera.slice(start, start + batchSize);
    
    sliced.forEach((item, i) => {
      grid.addWidget({
        x: 0,
        y: 0,
        w: 4,
        h: 4,
        content: `
          <div class="cam h-full w-full"
            data-id="${item.id}"
            data-hls="${item.hls}"
            data-alias="${item.alias}"
            data-nvr-id="${item.nvr_id}"
            data-mon="${item.monitor_id}"
          >
            <div class="thumb relative h-full w-full">
                <video class="vid h-full w-full object-cover" muted playsinline autoplay></video>

                <!-- fullscreen -->
                <button class="btn ghost absolute top-[8px] right-[8px] z-[1] hover:cursor-pointer hover:bg-slate-400" onclick="fsTileFull(event,this)" title="Fullscreen">⤢</button>

                <!-- label -->
                <button 
                  onclick="getCameraVideo('${item.nvr_id}', '${item.monitor_id}')" 
                  title="Click to access the video" 
                  class="absolute left-2 top-2 bg-slate-900/50 border border-slate-200 rounded-md px-2 py-1 text-[10px] font-bold hover:cursor-pointer hover:bg-violet-900/50"
                >
                  ${item.nvr} / ${item.monitor_id}
                </button>
            </div>
          </div>
        `
      });
    });

    // Make the grid fill the empty space
    grid.compact();

    // Initialize the HLS after adding the grids
    initVideos();
  }

  // Play or pause the slideshow
  function togglePlay() {
    if (isPlay) {
      stopSlider();
      isPlay = false;
      $('#pauseBtn').html('Play');
    } else {
      startSlider();
      isPlay = true;
      $('#pauseBtn').html('Pause');
    }
  }

  // Manual control - Next button
  document.getElementById('nextBtn').addEventListener('click', () => {
    start += batchSize;
    
    // Reset when reaching the end
    if (start >= arrayCamera.length) {
      start = 0; 
    }
    
    // Render the slide immediately after changing the start
    renderSlide(); 
    stopSlider();
  });

  // Manual control - Previous button
  document.getElementById('prevBtn').addEventListener('click', () => {
    start -= batchSize;
    
    // Make user can access the 'last page' if they at the 'first page'
    if (start < 0) {
      start = arrayCamera.length - batchSize;
    }

    renderSlide();
    stopSlider();
  });

  // Function to start auto sliding
  function startSlider() {

    // Need to render the slide first to init the grid and video
    // Causing un-distrubing bug: when the slider is play, the first page will be rendered twice before continue to the next page and the rest slideshow progress will be normal (the first page show only once)
    renderSlide();
    $('#pauseBtn').html('Pause');

    sliderInterval = setInterval(() => {
      renderSlide();

      // Move start index forward
      start += batchSize;
      if (start >= arrayCamera.length) {
        start = 0;
      }
      
    // Get the slide show duration per page from the select form in the dashboard (left of the "Slideshow Camera" button) 
    }, ($('#slideMsSel').val() ?? 5) * 1000);
  }

  // Function to pause the slider
  function stopSlider() {
    clearInterval(sliderInterval);
    $('#pauseBtn').html('Play');
  }

  /* ====== HLS attach (PERSIS yang jalan) ====== */
  function attachHls(videoEl, src){
    if (!videoEl) return null;
    videoEl.style.width = '100%';
    videoEl.style.height = '100%';
    videoEl.style.objectFit = 'cover';
    videoEl.style.background = '#000';

    if (Hls.isSupported()) {
      const hls = new Hls({
        maxBufferLength: 10,
        manifestLoadingTimeOut: 20000,
        autoStartLoad: true,
        enableWorker: true,
        lowLatencyMode: true,
      });

      hls.loadSource(src);
      hls.attachMedia(videoEl);

      hls.on(Hls.Events.ERROR, function (event, data) {
        if (data.fatal) {
          switch (data.type) {
            case Hls.ErrorTypes.NETWORK_ERROR:
              console.warn("Network error, trying to recover...");
              hls.startLoad();
              break;
            case Hls.ErrorTypes.MEDIA_ERROR:
              console.warn("Media error, trying to recover...");
              hls.recoverMediaError();
              break;
            default:
              hls.destroy();
              break;
          }
        }
      });
    } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
      videoEl.src = src;
    }

    return null;
  }

  // Function to initialize the HLS video inside the grid
  function initVideos(){
    document.querySelectorAll('.cam').forEach(card => {
      card._hlsObj = attachHls(card.querySelector('.vid'), card.dataset.hls);
    });
  };

  // Function to initialize the grids (used with GridStack options)
  function initGrid() {
    grid = GridStack.init({
      column: 12,
      cellHeight: customCellHeight,
      float: false,
      resizable: {
        handles: 'all'
      },
      maxRow: 12,
    }).compact();
  }

  function setGridStatic() {
    if (currentViewportWidth <= 850) {
      grid.setStatic(true);
    } else {
      grid.setStatic(false);
    }
  }

  // Make the selected camera grid full screen in dashboard
  function fsTile(ev, btn, type){
    ev.stopPropagation();

    if (isModalOpened) {
     
      let id = $('#modalSlideBody > :first-child').attr('id');
      let closedComponent = $('#modalSlideBody > :first-child');
     
      if (currentViewportWidth <= 850) {
        $('#modalSlideBody').removeClass('h-[90vw]').removeClass('rotate-90').addClass('w-[85%]');
      }

      $('#modalSlide').addClass('hidden').removeClass('flex');
      isModalOpened = false;

      $(`[data-id="${id}"]`).html(closedComponent);
      $('body').addClass('overflow-y-scroll');

    } else {
     
      const elem = btn.closest('.cam').querySelector('.thumb');

      if (currentViewportWidth <= 850) {
        $('#modalSlideBody').removeClass('w-[85%]').addClass('rotate-90').addClass('h-[90vw]');
      }
      
      $('#modalSlide').removeClass('hidden').addClass('flex');
      $('#modalSlideBody').html(elem);
      
      $('body').addClass('overflow-y-hidden');
      isModalOpened = true;
    }
  }

  // To make the selected camera grid full screen while in slide show mode
  // Separated with the function used in the dashboard's grids to prevent confussion 
  function fsTileFull(ev, btn){
    ev.stopPropagation();

    if (isModalFullOpened) {

      let id = $('#modalSlideFullBody > :first-child').attr('id');
      let closedComponent = $('#modalSlideFullBody > :first-child');

      $('#modalSlideFull').removeClass('flex').addClass('hidden');
      isModalFullOpened = false;
     
      $(`[data-id="${id}"]`).html(closedComponent);

      startSlider();

    } else {
      
      const elem = btn.closest('.cam').querySelector('.thumb');
      
      $('#modalSlideFull').removeClass('hidden').addClass('flex');
      $('#modalSlideFullBody').html(elem);
      
      isModalFullOpened = true;

      stopSlider();
    }
  }

  // Activate the slideshow mode 
  btnSlide?.addEventListener('click', () => {

    // Get all cameras data and progress the data inside renderSlide() function
    $.ajax({
      url: "/dashboard/getAllCameras" ,
      type: 'GET',
      success: function(response) {
        arrayCamera = response.tiles;
        
        $('#mainGrid').removeClass('grid-stack');
        $('#slideDiv').addClass('grid-stack');
        $('#mainSlideDiv').removeClass('hidden').addClass('block');

        document.getElementById('mainSlideDiv').requestFullscreen();
        customCellHeight = '12vh';

        renderSlide();
        initGrid();
        initVideos();
        startSlider();
      },
      error: function(xhr) {
          alert('Error: ' + xhr.responseText);
      }
    });
  });

  // When the user click the label of the grid, redirect the user to "Videos" page with the selected camera
  function getCameraVideo(nvrId, mon) {
    const qs = new URLSearchParams({ nvr_id: nvrId, mon });
    window.open('/videos?'+qs.toString(), '_blank');
  }

  // A listener to reload the page if user exited from slideshow mode
  document.addEventListener('fullscreenchange', () => {if (!document.fullscreenElement) window.location.reload();});
  
  $(document).ready(() => {
    initGrid();
    setGridStatic();
    initVideos();
  })

</script>

<style>
  .grid-stack .grid-stack-item {
    transition: transform 300ms ease, width 300ms ease, height 300ms ease;
  }

  .grid-stack>.grid-stack-item>.grid-stack-item-content {
    overflow-y: hidden;
  }
</style>
