// ZENIN — interaksi ringan (menu mobile & pratinjau nama file)
(function () {
  var sidebar = document.getElementById('sidebar');
  var scrim = document.getElementById('scrim');
  var btn = document.getElementById('menuBtn');
  function toggle(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    scrim.classList.toggle('show', open);
  }
  if (btn) btn.addEventListener('click', function () { toggle(!sidebar.classList.contains('open')); });
  if (scrim) scrim.addEventListener('click', function () { toggle(false); });

  var foto = document.getElementById('foto');
  var name = document.getElementById('uploadName');
  var box = document.getElementById('upload');
  if (foto && name) {
    foto.addEventListener('change', function () {
      name.textContent = foto.files.length ? foto.files[0].name : 'Pilih atau seret foto ke sini';
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      box.addEventListener(ev, function () { box.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      box.addEventListener(ev, function () { box.classList.remove('drag'); });
    });
  }
})();
