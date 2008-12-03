gettext = _ = function(message) {
  var translation;
  if (Translation && message in Translation) {
    return Translation[message];
  } else {
    return message;
  }
};

ngettext = function(singular, plural, count) {
  if (count == 1) {
    return gettext(singular);
  } else {
    return gettext(plural);
  }
};
