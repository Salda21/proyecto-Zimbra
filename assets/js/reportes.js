
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelector('#generateReport')?.addEventListener('click',()=>{
  const rows=[['Indicador','Valor'],['Visitantes','12540'],['Descargas','3240'],['Prospectos','820'],['Ventas','245']];
  const html='<table>'+rows.map(r=>'<tr>'+r.map(c=>'<td>'+c+'</td>').join('')+'</tr>').join('')+'</table>';
  const blob=new Blob(['\\ufeff',html],{type:'application/vnd.ms-excel'});const url=URL.createObjectURL(blob);
  const a=document.createElement('a');a.href=url;a.download='reporte_zimbraconnect.xls';a.click();URL.revokeObjectURL(url);
 });
});
