/* Leaflet loader for Project Locate on Map */
(function(){
  if (window.L) return;
  var s = document.createElement('script');
  s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  s.onload = function(){ window.dispatchEvent(new Event('leaflet-loaded')); };
  document.head.appendChild(s);
})();