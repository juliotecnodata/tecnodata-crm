<?php
declare(strict_types=1);

final class PdfCanvas {
 private const WIDTH=595.28;
 private const HEIGHT=841.89;
 private array $pages=[];
 private int $page=0;

 public function addPage(): int{$this->pages[]='';$this->page=count($this->pages)-1;return $this->page;}
 public function setPage(int $page): void{$this->page=$page;}
 public function pageCount(): int{return count($this->pages);}
 private function cmd(string $command): void{$this->pages[$this->page].=$command."\n";}
 private static function num(float $value): string{return rtrim(rtrim(number_format($value,2,'.',''),'0'),'.');}
 private static function encoded(string $text): string{
  $encoded=iconv('UTF-8','Windows-1252//TRANSLIT',$text);
  if($encoded===false)$encoded=preg_replace('/[^\x20-\x7E]/','?', $text)??$text;
  return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)','',' '],$encoded);
 }
 public function text(float $x,float $y,string $text,float $size=9,bool $bold=false,array $color=[32,48,56],string $align='L',float $width=0): void{
  if($align!=='L'&&$width>0){$estimate=mb_strlen($text)*$size*0.48;if($align==='R')$x+=max(0,$width-$estimate);elseif($align==='C')$x+=max(0,($width-$estimate)/2);}
  [$r,$g,$b]=$color;$py=self::HEIGHT-$y;
  $this->cmd('BT /'.($bold?'F2':'F1').' '.self::num($size).' Tf '.self::num($r/255).' '.self::num($g/255).' '.self::num($b/255).' rg 1 0 0 1 '.self::num($x).' '.self::num($py).' Tm ('.self::encoded($text).') Tj ET');
 }
 public function rect(float $x,float $y,float $w,float $h,array $fill,?array $stroke=null,float $lineWidth=.5): void{
  [$r,$g,$b]=$fill;$py=self::HEIGHT-$y-$h;$command=self::num($r/255).' '.self::num($g/255).' '.self::num($b/255).' rg ';
  if($stroke){[$sr,$sg,$sb]=$stroke;$command.=self::num($sr/255).' '.self::num($sg/255).' '.self::num($sb/255).' RG '.self::num($lineWidth).' w ';}
  $command.=self::num($x).' '.self::num($py).' '.self::num($w).' '.self::num($h).' re '.($stroke?'B':'f');$this->cmd($command);
 }
 public function line(float $x1,float $y1,float $x2,float $y2,array $color=[220,227,230],float $width=.5): void{
  [$r,$g,$b]=$color;$this->cmd(self::num($r/255).' '.self::num($g/255).' '.self::num($b/255).' RG '.self::num($width).' w '.self::num($x1).' '.self::num(self::HEIGHT-$y1).' m '.self::num($x2).' '.self::num(self::HEIGHT-$y2).' l S');
 }
 public static function wrap(string $text,int $maxChars): array{
  $text=trim(preg_replace('/\s+/u',' ',$text)??$text);if($text==='')return [''];$words=preg_split('/\s+/u',$text)?:[];$lines=[];$line='';
  foreach($words as $word){$candidate=$line===''?$word:$line.' '.$word;if(mb_strlen($candidate)<=$maxChars){$line=$candidate;continue;}if($line!=='')$lines[]=$line;$line=$word;}
  if($line!=='')$lines[]=$line;return $lines?:[''];
 }
 public function output(string $title='Proposta Comercial'): string{
  $count=count($this->pages);$objects=[];$objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
  $kids=[];for($i=0;$i<$count;$i++)$kids[]=(5+$i*2).' 0 R';
  $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.$count.' >>';
  $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
  $objects[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
  foreach($this->pages as $i=>$stream){$pageObj=5+$i*2;$contentObj=$pageObj+1;$objects[$pageObj]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::WIDTH.' '.self::HEIGHT.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentObj.' 0 R >>';$objects[$contentObj]='<< /Length '.strlen($stream).' >>' . "\nstream\n".$stream."endstream";}
  $info=count($objects)+1;$objects[$info]='<< /Title ('.self::encoded($title).') /Author (TECNODATA EDUCACIONAL LTDA) /Creator (Tecnodata CRM) /Producer (Tecnodata CRM PDF) >>';
  ksort($objects);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0=>0];foreach($objects as $number=>$body){$offsets[$number]=strlen($pdf);$pdf.=$number." 0 obj\n".$body."\nendobj\n";}
  $xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf('%010d 00000 n ',(int)($offsets[$i]??0))."\n";
  $pdf.="trailer\n<< /Size ".($max+1).' /Root 1 0 R /Info '.$info." 0 R >>\nstartxref\n".$xref."\n%%EOF";return $pdf;
 }
}

final class OrderProposalPdf {
 private PdfCanvas $pdf;
 private float $y=0;
 private array $data=[];
 private const LEFT=36.0;
 private const RIGHT=559.0;
 private const ORANGE=[244,151,46];
 private const TEAL=[25,107,116];
 private const TEXT=[32,48,56];
 private const MUTED=[103,119,126];
 private const LINE=[219,226,229];

 private static function decimal(mixed $value): float{$raw=trim((string)$value);if($raw==='')return 0.0;if(str_contains($raw,','))$raw=str_replace(['.',','],['','.'],$raw);return (float)$raw;}
 private static function money(float $value): string{return 'R$ '.number_format($value,2,',','.');}
 private static function number(float $value,int $decimals=2): string{return number_format($value,$decimals,',','.');}
 private static function date(string $value): string{return $value!==''&&strtotime($value)?date('d/m/Y',strtotime($value)):'-';}
 private static function document(string $value): string{$digits=preg_replace('/\D+/','',$value);if(strlen($digits)===14)return substr($digits,0,2).'.'.substr($digits,2,3).'.'.substr($digits,5,3).'/'.substr($digits,8,4).'-'.substr($digits,12,2);if(strlen($digits)===11)return substr($digits,0,3).'.'.substr($digits,3,3).'.'.substr($digits,6,3).'-'.substr($digits,9,2);return $value?:'-';}

 public static function fromForm(array $form,array $user,?int $draftId=null,array $options=[]): array{
  $clientId=(int)($form['client_id']??0);$client=$clientId>0?DB::one('SELECT * FROM clients WHERE id=?'.(!empty($options['allow_inactive_client'])?'':' AND active=1'),[$clientId]):null;
  if(!$client)throw new RuntimeException('Selecione um cliente válido antes de gerar o PDF.');
  $items=json_decode((string)($form['items_json']??'[]'),true);if(!is_array($items)||!$items)throw new RuntimeException('Inclua ao menos um produto antes de gerar a proposta em PDF.');
  $clientForm=ClientService::formFromClient($client);$sellerCode=($user['role']??'')==='seller'?(string)($user['seller_omie_code']??''):(string)($form['seller_omie_code']??'');
  $sellerName=(string)(DB::scalar('SELECT name FROM sellers WHERE omie_code=?',[$sellerCode])?:($user['name']??$sellerCode?:'Não informado'));
  $termCode=(string)($form['payment_term']??'');$term=$termCode!==''?DB::one('SELECT code,description,installments,days_list FROM payment_terms WHERE code=?',[$termCode]):null;
  $carrierCode=(string)($form['carrier_code']??'');$carrier=$carrierCode!==''?DB::one('SELECT name,phone,city,uf FROM clients WHERE omie_code=?',[$carrierCode]):null;
  $data=[
   'number'=>(string)($options['number']??($draftId?'R'.str_pad((string)$draftId,5,'0',STR_PAD_LEFT):date('Ymd-His'))),'draft_id'=>$draftId,'generated_by'=>(string)($user['name']??'Usuário CRM'),
   'title'=>(string)($options['title']??'PROPOSTA COMERCIAL'),'continued_title'=>(string)($options['continued_title']??'PROPOSTA - CONTINUAÇÃO'),'document_note'=>(string)($options['document_note']??'Documento para aprovação - não faturado'),
   'client'=>['name'=>(string)$client['name'],'document'=>(string)($client['document']??''),'email'=>(string)($client['email']??''),'phone'=>(string)($client['phone']??''),'contact'=>(string)($clientForm['contact_name']??''),'address'=>(string)($clientForm['address']??''),'number'=>(string)($clientForm['address_number']??''),'complement'=>(string)($clientForm['complement']??''),'neighborhood'=>(string)($clientForm['neighborhood']??''),'city'=>(string)($client['city']??''),'uf'=>(string)($client['uf']??''),'zip'=>(string)($clientForm['zip_code']??'')],
   'seller'=>$sellerName,'forecast'=>(string)($form['forecast_date']??''),'items'=>$items,'notes'=>(string)($form['notes']??''),'freight_value'=>self::decimal($form['freight_value']??0),
   'freight_mode'=>(string)($form['freight_mode']??'9'),'carrier'=>(string)($carrier['name']??''),'carrier_phone'=>(string)($carrier['phone']??''),'volumes'=>(string)($form['volumes']??''),'gross_weight'=>(string)($form['gross_weight']??''),
   'term'=>$term?:['code'=>$termCode,'description'=>'Não informado','installments'=>0,'days_list'=>''],'custom_installments'=>(string)($form['custom_installments']??'N'),'installments_json'=>(string)($form['installments_json']??'[]'),
   'total_override'=>array_key_exists('total_override',$options)?(float)$options['total_override']:null
  ];
  $safeNumber=preg_replace('/[^A-Za-z0-9_-]+/','-',(string)$data['number'])?:date('Ymd-His');$prefix=(string)($options['filename_prefix']??'proposta');
  $builder=new self();return ['content'=>$builder->build($data),'filename'=>$prefix.'-'.$safeNumber.'.pdf','data'=>$data];
 }

 public static function fromOrder(int $id,array $user): array{
  $detail=OrderService::orderDetail($id,$user);$order=(array)$detail['order'];$raw=(array)$detail['raw'];$header=(array)($raw['cabecalho']??[]);$freight=(array)($raw['frete']??[]);$items=[];
  foreach((array)$detail['items'] as $row){$product=(array)($row['product']??[]);$items[]=['sku'=>(string)($product['codigo']??$product['codigo_produto']??''),'description'=>(string)($product['descricao']??'Produto'),'ncm'=>(string)($product['ncm']??''),'quantity'=>(float)($product['quantidade']??0),'unit'=>(string)($product['unidade']??'UN'),'unit_price'=>(float)($product['valor_unitario']??0),'discount_type'=>(string)($product['tipo_desconto']??'V'),'discount_value'=>(float)(((string)($product['tipo_desconto']??'V'))==='P'?($product['percentual_desconto']??0):($product['valor_desconto']??0)),'line_total_override'=>array_key_exists('valor_total',$product)?(float)$product['valor_total']:null];}
  $installments=[];foreach((array)($raw['lista_parcelas']['parcela']??[]) as $parcel){if(!is_array($parcel))continue;$due=(string)($parcel['data_vencimento']??'');if($due!=='')$due=date('Y-m-d',strtotime(str_replace('/','-',$due)));$installments[]=['value'=>(float)($parcel['valor']??0),'due_date'=>$due];}
  $form=['client_id'=>(int)($order['client_id']??0),'seller_omie_code'=>(string)($order['seller_omie_code']??''),'forecast_date'=>(string)($order['forecast_date']??$order['order_date']??''),'payment_term'=>(string)($header['codigo_parcela']??''),'items_json'=>json_encode($items,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'notes'=>(string)($raw['observacoes']['obs_venda']??''),'freight_value'=>(string)($freight['valor_frete']??0),'freight_mode'=>(string)($freight['modalidade']??'9'),'carrier_code'=>(string)($freight['codigo_transportadora']??''),'volumes'=>(string)($freight['quantidade_volumes']??''),'gross_weight'=>(string)($freight['peso_bruto']??''),'custom_installments'=>$installments?'S':'N','installments_json'=>json_encode($installments,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
  $number=(string)($order['number']??$order['omie_code']??$id);return self::fromForm($form,$user,null,['number'=>$number,'title'=>'PEDIDO DE VENDA','continued_title'=>'PEDIDO - CONTINUAÇÃO','document_note'=>'Documento gerado pelo CRM - pedido integrado','filename_prefix'=>'pedido','allow_inactive_client'=>true,'total_override'=>(float)($order['total']??0)]);
 }

 public function build(array $data): string{
  $this->pdf=new PdfCanvas();$this->data=$data;$this->newPage();$this->clientSection();$this->itemsSection();$this->paymentSection();$this->otherSection();$this->freightSection();
  $pages=$this->pdf->pageCount();for($i=0;$i<$pages;$i++){$this->pdf->setPage($i);$this->pdf->line(self::LEFT,798,self::RIGHT,798,[225,230,232]);$this->pdf->text(self::LEFT,815,'Gerado em '.date('d/m/Y').' às '.date('H:i').' por '.($data['generated_by']??'Usuário CRM'),7.5,false,self::MUTED);$this->pdf->text(480,815,'Página '.($i+1).' de '.$pages,7.5,false,self::MUTED,'R',79);}
  return $this->pdf->output((string)($data['title']??'Documento comercial').' '.$data['number']);
 }
 private function newPage(bool $continued=false): void{
  $this->pdf->addPage();$this->pdf->rect(36,28,40,40,self::TEAL);$this->pdf->rect(63,28,13,13,[249,192,44]);$this->pdf->text(43,53,'TD',16,true,[255,255,255]);
  $this->pdf->text(86,40,'TECNODATA EDUCACIONAL LTDA',14,true,self::TEXT);$this->pdf->text(86,55,'www.tecnodataeducacional.com.br',8.5,false,self::MUTED);
  $this->pdf->text(365,36,'CNPJ 02.117.348/0001-99',8,false,self::TEXT,'R',194);$this->pdf->text(365,49,'Rua Suécia, 623 - Curitiba/PR',8,false,self::TEXT,'R',194);$this->pdf->text(365,62,'(41) 3361-1800',8,false,self::TEXT,'R',194);
  $title=$continued?(string)($this->data['continued_title']??'CONTINUAÇÃO'):(string)($this->data['title']??'DOCUMENTO COMERCIAL');$label=str_contains($title,'PEDIDO')?'Pedido nº ':'Proposta nº ';
  $this->pdf->line(self::LEFT,81,self::RIGHT,81,self::TEAL,1.2);$this->pdf->text(self::LEFT,105,$title,$continued?16:20,true,self::TEXT);
  $this->pdf->text(370,103,$label.($this->data['number']??'-'),10,true,self::TEAL,'R',189);$this->pdf->text(330,118,(string)($this->data['document_note']??''),8,false,self::MUTED,'R',229);$this->y=142;
 }
 private function ensure(float $height): void{if($this->y+$height>785)$this->newPage(true);}
 private function section(string $title,string $subtitle=''): void{$this->ensure(34);$this->pdf->rect(self::LEFT,$this->y,523,25,[244,247,248]);$this->pdf->rect(self::LEFT,$this->y,5,25,self::ORANGE);$this->pdf->text(49,$this->y+16,$title,11,true,self::TEXT);if($subtitle!=='')$this->pdf->text(330,$this->y+16,$subtitle,8,false,self::MUTED,'R',218);$this->y+=35;}
 private function label(float $x,float $y,string $label,string $value,float $width=220): float{$this->pdf->text($x,$y,$label,8,true,self::TEXT);$lines=PdfCanvas::wrap($value!==''?$value:'-',max(12,(int)($width/5)));foreach($lines as $i=>$line)$this->pdf->text($x+70,$y+$i*11,$line,8,false,self::TEXT);return max(11,count($lines)*11);}
 private function clientSection(): void{
  $c=(array)$this->data['client'];$this->section('Informações do cliente');$this->pdf->text(self::LEFT,$this->y,(string)($c['name']??'-'),12,true,self::TEXT);$this->y+=20;
  $left=$this->y;$lh=0;$lh+=$this->label(self::LEFT,$left+$lh,'Contato:',(string)($c['contact']??''));$lh+=$this->label(self::LEFT,$left+$lh,'CPF/CNPJ:',self::document((string)($c['document']??'')));$lh+=$this->label(self::LEFT,$left+$lh,'Telefone:',(string)($c['phone']??''));$lh+=$this->label(self::LEFT,$left+$lh,'E-mail:',(string)($c['email']??''),205);
  $address=trim((string)($c['address']??'').(!empty($c['number'])?', '.$c['number']:'').(!empty($c['complement'])?' - '.$c['complement']:''));$right=$this->y;$rh=0;$rh+=$this->label(310,$right+$rh,'Endereço:',$address,245);$rh+=$this->label(310,$right+$rh,'Bairro:',(string)($c['neighborhood']??''),245);$city=trim((string)($c['city']??'').(!empty($c['uf'])?' - '.$c['uf']:'').(!empty($c['zip'])?' - CEP '.$c['zip']:''));$rh+=$this->label(310,$right+$rh,'Cidade:',$city,245);$this->y+=max($lh,$rh)+14;
 }
 private function itemNet(array $item): float{if(array_key_exists('line_total_override',$item)&&$item['line_total_override']!==null)return max(0,(float)$item['line_total_override']);$gross=max(0,(float)($item['quantity']??0))*max(0,(float)($item['unit_price']??0));$value=max(0,(float)($item['discount_value']??$item['discount']??0));$discount=(string)($item['discount_type']??'V')==='P'?$gross*min(100,$value)/100:$value;return max(0,$gross-$discount);}
 private function itemHeader(): void{$this->pdf->rect(self::LEFT,$this->y,523,20,self::ORANGE);$cols=[[36,62,'Código','L'],[98,236,'Descrição','L'],[334,61,'NCM','L'],[395,50,'Quant.','R'],[445,48,'Unit.','R'],[493,66,'Total','R']];foreach($cols as [$x,$w,$label,$align])$this->pdf->text($x+4,$this->y+13,$label,7.5,true,[255,255,255],$align,$w-8);$this->y+=20;}
 private function itemsSection(): void{
  $itemSection=str_contains((string)($this->data['title']??''),'PEDIDO')?'Itens do pedido':'Itens da proposta';$this->section($itemSection);$this->itemHeader();$subtotal=0.0;$rowIndex=0;
  foreach((array)$this->data['items'] as $item){if(!is_array($item))continue;$description=(string)($item['description']??'Produto');$lines=PdfCanvas::wrap($description,53);$height=max(20,8+count($lines)*10);if($this->y+$height>742){$this->newPage(true);$this->section($itemSection,'continuação');$this->itemHeader();}$fill=$rowIndex%2?[252,247,240]:[249,251,251];$this->pdf->rect(self::LEFT,$this->y,523,$height,$fill);$net=$this->itemNet($item);$subtotal+=$net;
   $this->pdf->text(40,$this->y+13,(string)($item['sku']??''),7.5,false,self::TEXT);foreach($lines as $i=>$line)$this->pdf->text(102,$this->y+13+$i*10,$line,7.5,false,self::TEXT);$this->pdf->text(338,$this->y+13,(string)($item['ncm']??''),7.5,false,self::TEXT);$this->pdf->text(398,$this->y+13,self::number((float)($item['quantity']??0),2).' '.(string)($item['unit']??'UN'),7.5,false,self::TEXT,'R',43);$this->pdf->text(448,$this->y+13,self::number((float)($item['unit_price']??0),4),7.5,false,self::TEXT,'R',40);$this->pdf->text(497,$this->y+13,self::number($net,2),7.5,false,self::TEXT,'R',58);$this->y+=$height;$rowIndex++;
  }
  $freight=(float)($this->data['freight_value']??0);$calculated=$subtotal+$freight;$total=$this->data['total_override']!==null?(float)$this->data['total_override']:$calculated;$adjustments=$total-$calculated;$this->data['subtotal']=$subtotal;$this->data['total']=$total;$showAdjustments=abs($adjustments)>=.01;$this->ensure($showAdjustments?78:62);$x=405;$this->pdf->text($x,$this->y+15,'Subtotal:',9,true,self::TEXT);$this->pdf->text(493,$this->y+15,self::money($subtotal),9,false,self::TEXT,'R',66);$this->pdf->text($x,$this->y+31,'Frete:',9,true,self::TEXT);$this->pdf->text(493,$this->y+31,self::money($freight),9,false,self::TEXT,'R',66);$totalOffset=39;if($showAdjustments){$this->pdf->text($x,$this->y+47,'Impostos/ajustes:',8,true,self::TEXT);$this->pdf->text(493,$this->y+47,self::money($adjustments),8,false,self::TEXT,'R',66);$totalOffset=55;}$this->pdf->rect(397,$this->y+$totalOffset,162,23,self::TEAL);$this->pdf->text(405,$this->y+$totalOffset+15,'TOTAL:',10,true,[255,255,255]);$this->pdf->text(477,$this->y+$totalOffset+15,self::money($total),10,true,[255,255,255],'R',76);$this->y+=$totalOffset+37;
 }
 private function installments(): array{
  if(($this->data['custom_installments']??'N')==='S'){$rows=json_decode((string)($this->data['installments_json']??'[]'),true);if(is_array($rows)&&$rows)return $rows;}
  $term=(array)($this->data['term']??[]);preg_match_all('/\d+/',(string)($term['days_list']??''),$matches);$days=array_map('intval',$matches[0]??[]);$count=max(0,(int)($term['installments']??0));if(!$days&&$count)$days=array_map(fn($i)=>$i*28,range(1,$count));if(!$days)return [];$totalCents=(int)round((float)$this->data['total']*100);$base=(int)floor($totalCents/count($days));$date=(string)($this->data['forecast']??date('Y-m-d'));$rows=[];foreach($days as $i=>$day)$rows[]=['value'=>($i===count($days)-1?$totalCents-$base*(count($days)-1):$base)/100,'due_date'=>date('Y-m-d',strtotime($date.' +'.$day.' days'))];return $rows;
 }
 private function paymentSection(): void{
  $term=(array)($this->data['term']??[]);$this->section('Condição de pagamento',(string)($term['description']??''));$rows=$this->installments();if(!$rows){$this->pdf->text(self::LEFT,$this->y,'Condição: '.((string)($term['description']??'Não informada')),9,false,self::TEXT);$this->y+=24;return;}
  $width=165;$index=0;foreach($rows as $row){if($index%3===0){$this->ensure(54);if($index>0)$this->y+=8;}$col=$index%3;$x=self::LEFT+$col*174;$this->pdf->rect($x,$this->y,$width,44,[255,246,232],[245,218,181]);$this->pdf->text($x+8,$this->y+13,'Parcela '.($index+1),8,true,self::TEXT);$this->pdf->text($x+8,$this->y+28,self::date((string)($row['due_date']??'')),8,false,self::MUTED);$this->pdf->text($x+82,$this->y+28,self::money((float)($row['value']??0)),9,true,self::TEXT,'R',75);$index++;if($index%3===0)$this->y+=44;}if($index%3!==0)$this->y+=44;$this->y+=14;
 }
 private function otherSection(): void{
  $this->section('Outras informações');$this->label(self::LEFT,$this->y,'Emissão:',date('d/m/Y'));$this->y+=12;$this->label(self::LEFT,$this->y,'Previsão:',self::date((string)($this->data['forecast']??'')));$this->y+=12;$this->label(self::LEFT,$this->y,'Vendedor:',(string)($this->data['seller']??''));$this->y+=20;$notes=trim((string)($this->data['notes']??''));if($notes!==''){foreach(preg_split('/\R/u',$notes)?:[] as $paragraph){$lines=PdfCanvas::wrap($paragraph,105);$this->ensure(count($lines)*11+4);foreach($lines as $line){$this->pdf->text(self::LEFT,$this->y,$line,8.5,false,self::TEXT);$this->y+=11;}$this->y+=3;}}$this->y+=8;
 }
 private function freightSection(): void{
  $modes=['9'=>'Sem frete','0'=>'CIF - remetente','1'=>'FOB - destinatário','2'=>'Terceiros','3'=>'Próprio - remetente','4'=>'Próprio - destinatário'];$this->section('Transportador');$this->pdf->rect(self::LEFT,$this->y,523,19,[246,247,247]);$headers=[[40,'Transportadora'],[260,'Frete por conta'],[405,'Volumes'],[480,'Peso bruto']];foreach($headers as [$x,$label])$this->pdf->text($x,$this->y+13,$label,7.5,true,self::TEXT);$this->y+=19;$this->pdf->text(40,$this->y+14,(string)($this->data['carrier']?:'Sem transportadora'),8,false,self::TEXT);$this->pdf->text(260,$this->y+14,$modes[(string)($this->data['freight_mode']??'9')]??'Não informado',8,false,self::TEXT);$this->pdf->text(405,$this->y+14,(string)($this->data['volumes']?:'-'),8,false,self::TEXT);$weight=(string)($this->data['gross_weight']??'');$this->pdf->text(480,$this->y+14,$weight!==''?$weight.' kg':'-',8,false,self::TEXT);$this->y+=34;
 }
}
