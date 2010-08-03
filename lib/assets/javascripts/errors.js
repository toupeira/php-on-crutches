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
      } catch(e) {
        try {
          new Ajax.Request('/errors/debug_client', {
            parameters: {
              message: e.name+": "+e.message,
              file: e.fileName,
              line: e.lineNumber,
              trace: e.stack
            }
          });
        } catch(e) {
          alert(e.name+": "+e.message);
        }

        try {
          if (typeof(window.exception_handler) == 'function') {
            window.exception_handler(e);
          }
        } catch(e) {}
      }
    };
  }

  Object.extend(Function.prototype, { catchErrors: catchErrors });
})();
