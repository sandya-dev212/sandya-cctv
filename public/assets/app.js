const spinner = document.getElementById('spinner');
const modal   = document.getElementById('modal');
const modalBody = document.getElementById('modal-body');

function showSpinner(){ spinner.classList.remove('hidden'); }
function hideSpinner(){ spinner.classList.add('hidden'); }

function openModal(html){ modalBody.innerHTML = html; modal.classList.remove('hidden'); }
function closeModal(){ modal.classList.add('hidden'); modalBody.innerHTML=''; }

// placeholder stream handler (nanti akan pakai HLS .m3u8 dari Shinobi)
window.openStream = function(nvr, monitorId){
  showSpinner();
  setTimeout(() => {
    hideSpinner();
    openModal(`
      <div style="padding:16px">
        <h3 style="margin-top:0">Stream: ${nvr}/${monitorId}</h3>
        <div style="aspect-ratio:16/9;background:#0b1220;border:1px solid #1f2937;border-radius:12px;display:grid;place-items:center">
          <p style="color:#94a3b8">Player HLS m3u8 akan ditaruh di sini</p>
        </div>
      </div>
    `);
  }, 350);
}

window.openRecordings = function(nvr, monitorId){
  showSpinner();
  fetch('/dashboard/refresh')
    .then(r=>r.json())
    .then(_ => {
      hideSpinner();
      openModal(`
        <div style="padding:16px">
          <h3 style="margin-top:0">Recordings: ${nvr}/${monitorId}</h3>
          <div style="color:#94a3b8">Daftar rekaman akan tampil di sini (Get Videos).</div>
        </div>
      `);
    })
    .catch(()=> hideSpinner());
}
