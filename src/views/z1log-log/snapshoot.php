<?php
	\myzero1\adminlteiframe\gii\GiiAsset::register($this);
?>

<div id='snapshoot'>
	<div id='snapshoot-mask'></div>
<?php
    echo $content;
?>

</div>

<style type="text/css">
	#snapshoot{
	    position: relative;
	}
	#snapshoot-mask{
		top: 0;
	    left: 0;
	    bottom: 0;
	    right: 0;
	    position: absolute;
	    z-index: 999999999;
	}
</style>

<script type="text/javascript">
	document.oncontextmenu=new Function("event.returnValue=false;");
	document.onselectstart=new Function("event.returnValue=false;");
</script>