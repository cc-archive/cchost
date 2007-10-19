function dopost()
{
  var lines = document.getElementById('savedorder').innerHTML;
  lines = lines.split('|');
  var i = 0;
  var ids = [];
  for( i = 0; i < lines.length; i++ )
    ids.push(lines[i].replace(/.*span>([0-9]+).*$/, '$1'));
  var value = ids.join(',');
  document.getElementById('idorder').value = value;
  return true;
}
