<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">ADT Tools</a>
    </div>
    <div id="navbar" class="navbar-collapse collapse">

      <ul class="nav navbar-nav navbar-right">
        <li><a href="<?= base_url() ?>">Recover <span class="sr-only">(current)</span></a></li>
        <li><a href="<?= base_url() ?>github">System Update</a></li>
        <li><a href="<?= base_url() ?>filemanager">File Manager</a></li>
        <li><a href="<?= base_url() ?>dbmanager">DB Manager</a></li>
      </ul>
    </div><!--/.nav-collapse -->
  </div>
</nav>
<script type="text/javascript">
  $(function(){
    $('.nav li:nth-child(<?= $active_menu;?>)').addClass('active');
  });
</script>