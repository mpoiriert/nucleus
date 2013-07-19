<?php error_reporting(E_ALL & E_STRICT); ?>
<!doctype html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>

  <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap-responsive.min.css" />
  <link rel="stylesheet" type="text/css" href="vendor/tablesorter/themes/blue/style.css" />
  <link rel="stylesheet" type="text/css" href="app.css" />

  <script type="text/javascript" src="vendor/jquery-1.10.1.min.js"></script>
  <script type="text/javascript" src="vendor/json2.js"></script>
  <script type="text/javascript" src="vendor/underscore-min.js"></script>
  <script type="text/javascript" src="vendor/backbone-min.js"></script>
  <script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="vendor/tablesorter/jquery.tablesorter.min.js"></script>

  <script type="text/javascript" src="app.js"></script>
</head>
<body>
  <div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
      <a class="brand" href="#">Dashboard</a>
      <ul class="nav">

      </ul>
    </div>
  </div>

  <div id="main"></div>

  <script type="text/template" id="toolbar-tpl">
    <div class="btn-group">
    <% _.each(buttons, function(btn) { %>
        <button data-url="<%= btn.url %>" class="btn"><i class="icon-<%= btn.icon %>"></i> <%= btn.title %></button>
    <% }); %>
    </div>
  </script>

  <script type="text/template" id="form-action-tpl">
    <form action="<%= action.url %>" method="<%= action.method %>">
      <% _.each(action.fields, function(field) { %>
        <div class="control-group">
          <label class="control-label" for="<%= field.name %>"><%= field.label %></label>
          <div class="controls">
            <% if (field.type == 'textarea') { %>
              <textarea name="<%= field.name %>"><%= values[field.name] %></textarea>
            <% } else if (field.type == 'checkbox' || field.type == 'radio') { %>
              <input type="<%= field.type %>" name="<%= field.name %>" value="<%= field.value %>"
                <% if (values[field.name] == field.value) print("checked"); %>>
            <% } else { %>
              <input type="<%= field.type %>" name="<%= field.name %>" value="<%= values[field.name] %>">
            <% } %>
          </div>
        </div>
      <% }) %>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><%= action.btn_text || "Save" %></button>
      </div>
    </form>
  </script>

  <script type="text/template" id="list-action-tpl">
    <form>
      <table class="tablesorter">
        <thead>
          <tr>
            <th></th>
          <% _.each(action.columns, function(col) { %>
            <th><%= col %></th>
          <% }) %>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </form>
  </script>

</body>
</html>
