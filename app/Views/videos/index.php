<?php
$nvrs     = $nvrs ?? [];
$nvrId    = (int)($nvrId ?? 0);
$mon      = $mon ?? '';
$monitors = $monitors ?? [];
?>
<link rel="stylesheet" href="https://unpkg.com/flatpickr/dist/flatpickr.min.css">
<style>
.v-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0 16px}
.v-table{border-collapse:collapse}
.v-table th,.v-table td{padding:10px;border-bottom:1px solid #1f2937;text-align:left}
.v-modal-back{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:50}
.v-modal{width:min(1100px,92vw);background:#0b1220;border:1px solid #1f2937;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.5)}
.v-modal .v-head{padding:12px;border-bottom:1px solid #1f2937;text-align:center}
.v-modal .v-body{padding:18px}
.v-pill{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:10px;background:#7c3aed;border:none;color:#fff;font-weight:700;cursor:pointer}
.v-close{margin-top:8px;display:inline-block}
</style>

<p class="text-3xl font-bold text-white">Videos</p>

<div class="flex flex-col gap-3 mt-10">
  <div class="flex flex-col gap-1">
    <label class="font-bold">Select NVR</label>
    <select id="nvrSel" class="w-[25%] bg-slate-800 p-2 rounded-md hover:cursor-pointer max-[850px]:w-full">
      <option value="" class="truncate"><?= esc('Select NVR') ?></option>
      <?php foreach ($nvrs as $n): ?>
        <option class="truncate" value="<?= (int)$n['id'] ?>" <?= ($nvrId && (int)$nvrId===(int)$n['id'])?'selected':'' ?>>
          <?= esc($n['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  
  <div class="flex flex-col gap-1">
    <label class="font-bold">Select Camera</label>
    <select id="monSel" class="w-[25%] bg-slate-800 p-2 rounded-md hover:cursor-pointer max-[850px]:w-full">
      <option value="" class="truncate"><?= esc('Select Camera') ?></option>
      <?php if ($nvrId && $monitors): ?>
        <?php foreach ($monitors as $m): ?>
          <option class="truncate" value="<?= esc($m['mid']) ?>" <?= ($mon && $mon===$m['mid'])?'selected':'' ?>>
            <?= esc($m['mid'] . ' — ' . $m['name']) ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </div>

  <div class="flex flex-col gap-1">
    <label class="font-bold">Start</label>
    <input id="start" style="min-width:230px" class="w-[25%] bg-slate-800 p-2 rounded-md hover:cursor-pointer max-[850px]:w-full">
    <label class="font-bold">End</label>
    <input id="end" style="min-width:230px" class="w-[25%] bg-slate-800 p-2 rounded-md hover:cursor-pointer max-[850px]:w-full">
  </div>

  <button id="apply" class="v-pill w-[25%] flex justify-center mt-4 max-[850px]:w-full">Apply</button>
</div>

<div class="overflow-x-auto">
  <table class="v-table mt-10 w-full max-[850px]:min-w-[850px]" id="tbl">
    <thead>
      <tr>
        <th>Filename</th>
        <th>Waktu </th>
        <th>Size</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="3" style="color:#94a3b8">Pilih NVR & kamera.</td></tr>
    </tbody>
  </table>
</div>

<div id="vModalBack" class="v-modal-back">
  <div class="v-modal">
    <div class="v-head">
      <div id="vTitle" style="font-weight:600;margin-bottom:6px"></div>
      <button class="v-close v-pill" id="vClose">Close</button>
    </div>
    <div class="v-body" style="display:flex;justify-content:center">
      <video id="vPlayer" controls style="width:100%;max-width:1000px;max-height:70vh;background:#000;border-radius:12px;border:1px solid #1f2937"></video>
    </div>
    <div style="text-align:center;padding:0 18px 18px">
      <div id="vLink" style="margin-top:8px;color:#9ca3af;font-size:14px;word-break:break-all"></div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/flatpickr"></script>
<script>
const qs=s=>document.querySelector(s);
const nvrSel=qs('#nvrSel'), monSel=qs('#monSel'), tb=qs('#tbl tbody');

flatpickr("#start",{enableTime:true,dateFormat:"Y-m-d H:i:S",defaultDate:new Date(Date.now()-3*24*3600*1000),time_24hr: true});
flatpickr("#end",{enableTime:true,dateFormat:"Y-m-d H:i:S",defaultDate:new Date(),time_24hr: true});

// Ganti NVR → load monitors
nvrSel.addEventListener('change', async ()=>{
  const id = nvrSel.value;
  monSel.innerHTML = '<option value=""><?= esc('Select Camera') ?></option>';
  if(!id) return;
  const r = await fetch('/videos/monitors?nvr_id='+encodeURIComponent(id));
  const j = await r.json().catch(()=>({ok:false,items:[]}));
  if(!j.ok) return;
  j.items.forEach(m=>{
    const opt=document.createElement('option');
    opt.value=m.mid; opt.textContent=`${m.mid} — ${m.name}`;
    monSel.appendChild(opt);
  });
});

function toMs(s){ return Date.parse(s); }
async function loadData(){
  const nvr_id=nvrSel.value, mon=monSel.value;
  if(!nvr_id || !mon){
    tb.innerHTML='<tr><td colspan="3" style="color:#94a3b8">Pilih NVR & kamera.</td></tr>';
    return;
  }
  const start=toMs(qs('#start').value), end=toMs(qs('#end').value);
  const r = await fetch('/videos/data?'+new URLSearchParams({nvr_id,mon,start,end}).toString());
  const j = await r.json().catch(()=>({ok:false,data:[]}));
  const rows=j.data||[];
  tb.innerHTML = rows.length ? '' : '<tr><td colspan="3" style="color:#94a3b8">Tidak ada file pada rentang waktu tersebut.</td></tr>';
  rows.forEach(row=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td>${row.name}</td>
      <td>${row.time}</td>
      <td>${row.size||''} MB</td>
      <td><a href="#" data-play="${row.play}" class="v-pill">Play</a> <a href="${row.download}" target="_blank" rel="noopener"  class="v-pill">Download</a></td>`;
    tr.querySelector('a[data-play]').addEventListener('click',(e)=>{e.preventDefault();openPlayer(row.play);});
    tb.appendChild(tr);
  });
}
qs('#apply').addEventListener('click', loadData);

// Modal (centered)
const back=qs('#vModalBack'), v=qs('#vPlayer'), ttl=qs('#vTitle'), link=qs('#vLink');
function openPlayer(url){
  ttl.textContent = `${nvrSel.options[nvrSel.selectedIndex]?.text || ''} — ${monSel.value}`;
  v.src=url; v.currentTime=0; v.play().catch(()=>{});
  link.textContent=url;
  back.style.display='flex';
}
qs('#vClose').addEventListener('click',()=>{v.pause();v.removeAttribute('src');v.load();back.style.display='none';});
back.addEventListener('click',(e)=>{if(e.target===back)qs('#vClose').click();});

// Auto-load jika datang dari dashboard (ada nvrId & mon preset)
<?php if ($nvrId && $mon): ?>
  window.addEventListener('DOMContentLoaded', loadData);
<?php endif; ?>
</script>
