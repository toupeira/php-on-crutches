(function() {
  DebugToolbar = {};

  DebugToolbar.init = function() {
    $$('#debug-toolbar > div > a').each(function(link) {
      link.onclick = toggle_window.curry(link);
    });
  };

  DebugToolbar.toggle = function() {
    var toolbar = $('debug-toolbar');
    var link = $$('#debug-toolbar > div > a')[0];
    if (toolbar.hasClassName('disabled')) {
      toolbar.removeClassName('disabled');
      link.onclick = toggle_window.curry(link);
    } else {
      toggle_window(last, false);
      toolbar.addClassName('disabled');
      link.onclick = DebugToolbar.toggle;
    }
  };

  DebugToolbar.close = function() {
    toggle_window(last, false);
    $('debug-panel-shadow').remove();
    $('debug-toolbar').remove();
  };

  var last;

  var toggle_window = function(link, show) {
    if (!link) {
      return;
    };

    var win = link.next('div');
    var shadow = $('debug-panel-shadow');

    if (show === undefined || typeof(show) == 'object') {
      show = (win.style.display != 'block');
    }

    if (show) {
      toggle_window(last, false);
      last = link;

      win.clonePosition(link, {
        setTop: false,
        setWidth: false,
        setHeight: false
      });
      win.style.display = 'block';

      link.addClassName('active');

      shadow.style.width = win.getWidth() + 6 + 'px';
      shadow.style.height = win.getHeight() + 6 + 'px';
      shadow.style.left = win.offsetLeft - 3 + 'px';
      shadow.style.display = 'block';

      document.observe('click', reset);
    } else {
      shadow.hide();
      link.removeClassName('active');
      win.hide();
      document.stopObserving('click', reset);
    }

    return false;
  };

  var reset = function(event) {
    if (!Event.element(event).descendantOf('debug-toolbar')) {
      toggle_window(last, false);
    }
  }.bindAsEventListener(this);

  var open = function(link, win) {
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
  };
})();

document.observe('dom:loaded', DebugToolbar.init);
