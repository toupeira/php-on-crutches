function gettext(message) {
  if (Translations && message in Translations) {
    return Translations[message];
  } else {
    return message;
  }
}

var _ = gettext;

function ngettext(singular, plural, count) {
  if (count == 1) {
    return gettext(singular);
  } else {
    return gettext(plural);
  }
}
