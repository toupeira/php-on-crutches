function toggle(elem) {
  if (elem) {
    elem.style.display = (elem.style.display == 'none' ? 'block' : 'none');
  }
}

function toggle_folder(link) {
  toggle(link.nextSibling);
}

function toggle_source(link) {
  toggle(link.parentNode.nextSibling);
}
