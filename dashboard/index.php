<?php
  $config = json_decode(file_get_contents('config.json'), true);
  $baseurl = $config['base_url'] = dirname($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>

  <link rel="stylesheet" type="text/css" href="<?php echo $baseurl ?>/vendor/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo $baseurl ?>/vendor/bootstrap/css/bootstrap-responsive.min.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo $baseurl ?>/vendor/tablesorter/themes/blue/style.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo $baseurl ?>/app.css" />

  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/jquery-1.10.1.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/json2.js"></script>
  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/underscore-min.js"></script>
  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/backbone-min.js"></script>
  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/bootstrap/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="<?php echo $baseurl ?>/vendor/tablesorter/jquery.tablesorter.min.js"></script>

  <script type="text/javascript" src="<?php echo $baseurl ?>/app.js"></script>
  <script type="text/javascript">
    $(function() {
      Dashboard.start(<?php echo json_encode($config); ?>);
    });
  </script>
</head>
<body>
  <div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
      <a class="brand" href="#">Dashboard</a>
      <ul class="nav"></ul>
    </div>
  </div>

  <div id="main"></div>

  <script type="text/template" id="toolbar-tpl">
    <div class="btn-group">
    <% _.each(buttons, function(btn) { %>
        <a href="<%= base_url + btn.name %>" data-action="<%= btn.name %>" class="btn"><i class="icon-<%= btn.icon %>"></i> <%= btn.title %></a>
    <% }); %>
    </div>
  </script>

  <script type="text/template" id="form-action-tpl">
    <% _.each(fields, function(field) { %>
      <div class="control-group">
        <label class="control-label" for="<%= field.name %>"><%= field.title %></label>
        <div class="controls">
          <% if (field.field_type == 'textarea') { %>
            <textarea name="<%= field.name %>" data-type="<%= field.formated_type %>"><%= values[field.name] %></textarea>
          <% } else if (field.field_type == 'checkbox' || field.field_type == 'radio') { %>
            <input type="<%= field.field_type %>" name="<%= field.name %>" value="<%= field.value %>"
              data-type="<%= field.formated_type %>" <% if (values[field.name] == field.value) print("checked"); %>>
          <% } else { %>
            <input type="<%= field.field_type %>" name="<%= field.name %>" value="<%= values[field.name] %>"
              data-type="<%= field.formated_type %>">
          <% } %>
        </div>
      </div>
    <% }) %>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Submit</button>
    </div>
  </script>

  <script type="text/template" id="object-action-tpl">
    <dl>
    <% _.each(fields, function(field) { %>
      <dt><%= field.title %></dt>
      <dd>
        <%= model[field.name] %>
        <% if (field.identifier) { %>
          <input type="hidden" name="<%= field.name %>" value="<%= model[field.name] %>">
        <% } %>
      </dd>
    <% }) %>
    </dl>
  </script>

  <script type="text/template" id="list-action-tpl">
    <table class="tablesorter">
      <thead>
        <tr>
          <th></th>
        <% _.each(fields, function(f) { %>
          <th><%= f.title %></th>
        <% }) %>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </script>

</body>
</html>
