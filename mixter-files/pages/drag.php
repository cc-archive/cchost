
<div style="float:left;">
<h3>This is the first list</h3>
 <ul class="sortabledemo" id="firstlist" style="height:150px;width:200px;">
   <li class="green" id="firstlist_1">Item 1 from first list.</li>
   <li class="green" id="firstlist_2">Item 2 from first list.</li>
   <li class="green" id="firstlist_3">Item 3 from first list.</li>
 </ul>
</div>

<textarea id="serz" style="width:300px;height:200px" ></textarea>

 <script type="text/javascript">
 function on_drop()
 {
     $('serz').innerHTML = Sortable.serialize('firstlist');

 }

   Sortable.create("firstlist",
     {dropOnEmpty:true,constraint:false,onUpdate: on_drop});

 </script>
