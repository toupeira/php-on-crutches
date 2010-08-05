(function() {
  var slice = Array.prototype.slice;

  function update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function merge(array, args) {
    array = slice.call(array, 0);
    return update(array, args);
  }

  function catchErrors() {
    var __method = this, args = slice.call(arguments, 0);
    return function() {
      try {
        var a = merge(args, arguments);
        return __method.apply(this, a);
      } catch(error) {
        if (window.error_handler && window.error_handler.disabled) {
          throw error;
        }

        try {
          var trace = error.stack;
          if (!trace && arguments.callee) {
            trace = arguments.callee.name+'()';
          }

          new Ajax.Request('/errors/debug_client', {
            parameters: {
              message: error.name+(error.message ? ": "+error.message : ""),
              file: error.fileName || "(unknown)",
              line: error.lineNumber || "(unknown)",
              trace: trace
            }
          });
        } catch(e) {
          alert(e.name+": "+e.message);
        }

        try {
          if (typeof(window.error_handler) == 'function') {
            window.error_handler(error);
          }
        } catch(e) {}

        return false;
      }
    };
  }

  Object.extend(Function.prototype, { catchErrors: catchErrors });
})();
