<?# $Id$ ?>
<?

   function toggle_link($title, $id=null, $options=null) {
      if ($id) {
         $onclick = "\$('$id').toggle(); return false";
      } else {
         $onclick = '$(this.nextSibling).toggle(); return false';
      }
      return link_to($title, '#', array_merge((array) $options, array('onclick' => $onclick)));
   }

?>
