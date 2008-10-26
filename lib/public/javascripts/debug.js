var DebugToolbar = {
  init: function() {
    $$('#debug-toolbar > span').each(function(link) {
      link.onclick = DebugToolbar.toggle.bind(DebugToolbar, link);
    });

    DebugToolbar.reset = DebugToolbar.reset.bindAsEventListener(DebugToolbar);
  },

  toggle: function(link, show) {
    var win = link.next('div');

    if (show === undefined || typeof(show) == 'object') {
      show = (win.style.display != 'block');
    }

    if (show) {
      if (this.last) {
        this.toggle(this.last, false);
      }
      this.last = link;
      win.clonePosition(link, {
        setTop: false,
        setWidth: false,
        setHeight: false
      });
      win.style.display = 'block';
      link.addClassName('active');

      Event.observe(document, 'click', this.reset);
    } else {
      win.hide();
      link.removeClassName('active');
      Event.stopObserving(document, 'click', this.reset);
    }

    return false;
  },

  reset: function(event) {
    if (!Event.element(event).descendantOf('debug-toolbar') && this.last) {
      this.toggle(this.last, false);
    }
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
