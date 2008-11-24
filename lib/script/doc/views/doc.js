function toggle_source(elem) {
  var next = elem.parentNode.nextSibling;
  next.style.display = (next.style.display == 'block' ? 'none' : 'block');
}
