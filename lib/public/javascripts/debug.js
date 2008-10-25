var DebugToolbar = {
  init: function() {
    $$('#debug-toolbar > span > a').each(function(link) {
      link.href = '#';
      link.onclick = DebugToolbar.toggle.bind(DebugToolbar, link);
    });
  },

  toggle: function(link) {
    var win = link.parentNode.next('div');
    if (win.style.display == 'block') {
      win.hide();
    } else {
      if (this.last) {
        this.last.hide();
      }
      this.last = win;
      win.clonePosition(link, {
        setTop: false,
        setWidth: false,
        setHeight: false
      });
      win.style.display = 'block';
    }

    return false;
  },

  open: function(link, win) {
    new Ajax.Request(link.href, {
      onSuccess: function(req) {
        if (req.responseText) {
          win.update(req.responseText);
        }
      },
      onFailure: function(req) {
        alert("Couldn't load data (Error "+req.status+")");
      }
    });
  }
};

Event.observe(window, 'load', DebugToolbar.init);
