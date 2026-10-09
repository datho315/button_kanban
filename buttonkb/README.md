<<<<<<< HEAD
# Kanban Quick View block

`block_buttonkb` is a Moodle 4.5 block that installs a floating calendar button
on the site home page and the default personal Dashboard. Installation also adds
the block to existing personal Dashboards that already contain a block.

The popup shows:

- The current user's visible Kanban card count and active enrolled course count.
- Up to three enrolled courses with the nearest upcoming due dates from Moodle
  Assign activities linked to a Kanban activity in the same course.
- A link to `/my/courses.php`.

The block depends on `mod_kanban`. It does not store user data; displayed counts
and deadlines are calculated for the logged-in user when the page is rendered.
The floating button uses a locally bundled calendar SVG icon.
After installing the plugin through **Site administration > Notifications**,
purge Moodle caches if the block or its AMD JavaScript is not immediately
visible. The course/task summary is only shown to authenticated users.
=======
# button_kanban
button_kanban
>>>>>>> 668d716cd76af7471e1503694d16feaf7c5c283d
