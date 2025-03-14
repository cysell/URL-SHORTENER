<?php 
/**
 * ====================================================================================
 *                           某某短网址系统
 * ----------------------------------------------------------------------------------
 *  @copyright - This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in illegal activity. You must delete this software immediately or buy a proper
 *  license from https://www.domain.com
 *
 *	@license https://www.domain.com/license
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author 某某 (https://www.domain.com)
 * @link https://www.domain.com
 * @package 某某短网址系统
 * @subpackage Admin Class (Admin.class.php)
 */

class Admin{
  /**
   * [$sandbox description]
   * @var boolean
   */
  protected $sandbox = FALSE;
  /**
   * Authorized actions
   * @since 5.0
   **/
  protected $actions = ["ads","users","urls","payments","pages","settings","themes","languages","help","search","subscription","tools","plans","update","emails"];
  /**
   * Config
   * @since 4.0
   **/
  protected $config;
  /**
   *  DB
   * @since 4.0
   **/
  protected $db;
  /**
   * Admin Info
   * @since 4.0
   **/
  protected $user;
  /**
   * Admin URL
   * @since 4.0
   **/
  protected $url;
  /**
   *  Current Page 
   * @since 4.0
   **/
  protected $page;
  /**
   * Reserved Variable
   * @since 4.0
   **/
  protected $action;
  /**
   * Reserved Variable
   * @since 4.0
   **/  
  protected $do;
  /**
   * Reserved Variable
   * @since 4.0
   **/
  protected $id;
  /**
   * Admin Limit/Page
   * @since 4.0
   **/
  protected $limit = 20;

  /**
   * Construct Admin
   * @since 4.0
   **/
  public function __construct($config,$db){
    $this->config=$config;
    $this->db=$db;     
    $this->db->object=TRUE;
    $this->url="{$this->config["url"]}/admin";
    $this->page=(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"]!="0")?Main::clean($_GET["page"],3,TRUE):"1";  
    $this->check();
  }
  /**
   * Free Memory (don't needed but do it anyway)
   * @since 4.0
   **/
  public function __destruct(){
    unset($this->db,$this->user,$this->config);
  }
  /**
   * Check if user is logged and has admin privileges!
   * @since 4.0
   **/
  public function check(){
    if($info=Main::user()){
      if($user=$this->db->get("user",array("id"=>"?","auth_key"=>"?"),array("limit"=>1),array($info[0],$info[1]))){        
        if(!$user->admin) return Main::redirect("404");
        $this->logged=TRUE;
        $this->user=$user;     
        $user=NULL;
        // Unset sensitive information
        unset($this->user->password);
        unset($this->user->auth_key);          
        return TRUE;
      }
    }
    return Main::redirect("404");
  }  
  /**
   * Run Admin Panel
   * @since 4.0
   **/
  public function run(){
    if(isset($_GET["a"]) && !empty($_GET["a"])){
      $var=explode("/",$_GET["a"]);
      if(in_array($var[0],$this->actions) && method_exists("Admin", $var[0])){
        $this->action=Main::clean($var[0],3,TRUE);
        if(isset($var[1]) && !empty($var[1])) $this->do=Main::clean($var[1],3,TRUE);
        if(isset($var[2]) && !empty($var[2])) $this->id=Main::clean($var[2],3,TRUE);
        return $this->{$var[0]}();
      } 
      return Main::redirect("admin",array("danger","Oups! The page you are looking for doesn't exist."));
    }else{
      return $this->home();
    }
  }  
  /**
   * [isExtended description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  boolean [description]
   */
  protected function isExtended(){
    if(isset($this->config["stsk"]) && isset($this->config["stpk"]) && isset($this->config["pt"])) return TRUE;
    return FALSE;
  }
  /**
   * Admin Home Page
   * @since 4.0
   **/
  protected function home(){
    // Chart Data
    $urls=$this->db->get("url","",array("limit"=>8,"order"=>"date"));
    $top_urls=$this->db->get("url","",array("limit"=>8,"order"=>"click"));
    $users=$this->db->get("user","",array("limit"=>8,"order"=>"date"));
    $payments=$this->db->get("payment","",array("limit"=>8,"order"=>"date"));

    $this->charts();
    $topcountries=$this->countries();

    //$payments=$this->db->run("SELECT {$this->db->prefix}payment.*,{$this->db->prefix}user.email FROM {$this->db->prefix}payment INNER JOIN {$this->db->prefix}user ON {$this->db->prefix}payment.userid = {$this->db->prefix}user.id ORDER BY date DESC LIMIT 20",array(),TRUE);
    Main::set("title","管理后台");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
    /**
     *  Dashboard Chart Data Function
     *  Generate data and inject it into the homepage. Also append the flot library.
     *  @since 4.0
     */   
      protected function charts($filter="day",$span=30){
        if(isset($_GET["filter"])) $filter=$_GET["filter"];
        $new_date=array();  
        $new_clicks=array(); 
        $new_urls=array();        
        // Store as Array
        $this->db->object=FALSE;
        // Daily Stats
        if($filter=="monthly"){
          $span=12;

          $usersbydate = Main::cache_get("admin_user_month");
          if($usersbydate == null){
            $usersbydate=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"user"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));     
            Main::cache_set("admin_user_month", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_month");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"url"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));   
            Main::cache_set("admin_url_month", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_month");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));   
            Main::cache_set("admin_click_month", $clicks,15);
          }


          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("F Y",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("F Y",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("F Y",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('F Y', $timestamp)]=0;
              $timestamp -= 30*24 * 3600;
          }
        }elseif($filter=="yearly"){

          $span=8;


          $usersbydate = Main::cache_get("admin_user_year");
          if($usersbydate == null){
           $usersbydate=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"user"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30));      
            Main::cache_set("admin_user_year", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_year");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"url"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30)); 
   
            Main::cache_set("admin_url_year", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_year");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30));  
            Main::cache_set("admin_click_year", $clicks,15);
          }

          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("Y",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("Y",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("Y",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('Y', $timestamp)]=0;
              $timestamp -= 12*30*24 * 3600;
          }
        }else{

          $usersbydate = Main::cache_get("admin_user_daily");
          if($usersbydate == null){
           $usersbydate=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"user"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));        
            Main::cache_set("admin_user_daily", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_daily");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"url"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));
            Main::cache_set("admin_url_daily", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_daily");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));  
            Main::cache_set("admin_click_daily", $clicks,15);
          }
          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("d M",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("d M",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("d M",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('d M', $timestamp)]=0;
              $timestamp -= 24 * 3600;
          }            
        }
       
        $this->db->object=TRUE;
        $date=""; $var=""; $date1=""; $var1=""; $date2=""; $var2=""; $i=0; 

        foreach ($array as $key => $value) {
          $i++;
          if(isset($new_date[$key])){
            $var.="[".($span-$i).", ".$new_date[$key]."], ";
            $date.="[".($span-$i).",\"$key\"], ";
          }else{
            $var.="[".($span-$i).", 0], ";
            $date.="[".($span-$i).", \"$key\"], ";
          }
          if(isset($new_urls[$key])){
            $var1.="[".($span-$i).", ".$new_urls[$key]."], ";
            $date1.="[".($span-$i).",\"$key\"], ";
          }else{
            $var1.="[".($span-$i).", 0], ";
            $date1.="[".($span-$i).", \"$key\"], ";
          }  
          if(isset($new_clicks[$key])){
            $var2.="[".($span-$i).", ".$new_clicks[$key]."], ";
            $date2.="[".($span-$i).",\"$key\"], ";
          }else{
            $var2.="[".($span-$i).", 0], ";
            $date2.="[".($span-$i).", \"$key\"], ";
          }             
        }
        $data=array("registered"=>array($var,$date),"urls"=>array($var1,$date1),"clicks"=>array($var2,$date2));
        Main::admin_add("{$this->config["url"]}/static/js/flot.js","script",0);
        Main::admin_add("<script type='text/javascript'>var options = {
              series: {
                lines: { show: true, lineWidth: 2,fill: true},                
                points: { show: true, lineWidth: 2 }, 
                shadowSize: 0
              },
              grid: { hoverable: true, clickable: true, tickColor: 'transparent', borderWidth:0 },
              colors: ['#0da1f5', '#1ABC9C','#F11010'],
              xaxis: {ticks:[{$data["urls"][1]},{$data["clicks"][1]},{$data["registered"][1]}], tickDecimals: 0, color: '#999'},
              yaxis: {ticks:3, tickDecimals: 0, color: '#CFD2E0'},
              xaxes: [ { mode: 'time'} ]
          }; 
          var data = [{
              label: ' 网址 ',
              data: [{$data["urls"][0]}]
          },{
              label: ' 点击',
              data: [{$data["clicks"][0]}]
          },{
              label: ' 用户 ',
              data: [{$data['registered'][0]}]
          }];
          $.plot('#user-chart', data ,options);</script>",'custom',TRUE);        
      }
    /**
     *  Dashboard Country Function
     *  @since 1.1
     */     
     protected function countries(){
        $this->db->object=FALSE;
        $countries = Main::cache_get("admin_countries");
        if($countries == null){
          $countries=$this->db->get(array("count"=>"COUNT(country) as count, country as country","table"=>"stats"),"",array("group"=>"country","order"=>"count"));
          Main::cache_set("admin_countries",$countries,15);
        }
        $this->db->object=TRUE;
        $i=0;
        $top_countries=array();
        $country=array();
        foreach ($countries as $c) {
          $country[Main::ccode(ucwords($c["country"]),1)]=$c["count"];
          if($i<=10){
            if(!empty($c["country"])) $top_countries[ucwords($c["country"])]=$c["count"];
          }
          $i++;
        }
        Main::admin_add("<script type='text/javascript'>var data=".json_encode($country)."; $('#country-map').vectorMap({
          map: 'world_mill_en',
          backgroundColor: 'transparent',
          series: {
            regions: [{
              values: data,
              scale: ['#74CBFA', '#0da1f5'],
              normalizeFunction: 'polynomial'
            }]
          },
          onRegionLabelShow: function(e, el, code){
            if(typeof data[code]!='undefined') el.html(el.html()+' ('+data[code]+' Clicks)');
          }     
        });</script>","custom");
        return $top_countries;
     }        
  /**
    * Search
    * @since 4.0 
    **/
  protected function search(){
    if(empty($_GET["q"]) || strlen($_GET["q"])<3) Main::redirect("admin",array("danger","关键词必须大于3个字符."));
    $count="";
    $pagination="";
    $hideFilter=FALSE;
    $users=$data=$this->db->search("user",array("email"=>"?","username"=>"?"),array("limit"=>30),array("%{$_GET["q"]}%","%{$_GET["q"]}%"));

    $urls=$this->db->search("url",array("url"=>":q","alias"=>":q","meta_title"=>":q"),array("limit"=>30),array(":q"=>"%{$_GET["q"]}%"));

    $payments=$this->db->search("payment",array("userid"=>":q","tid"=>":q"),array("limit"=>30),array(":q"=>"%{$_GET["q"]}%"));
    $this->header();
    if(!$users && !$urls && !$payments){
      echo "<h3>未找到结果</h3> <p>您的关键字与任何结果都不匹配。请尝试其他关键字.</p>";
    }
    if($users){
      include($this->t("users"));
    }
    if($urls){
      include($this->t("urls"));
    }
    if($payments){
      include($this->t("payments"));
    }    
    $this->footer();    
  }
  /**
   * URLs
   * @since 4.2
   **/
  protected function urls($limit=""){
    if(in_array($this->do, array("edit","delete","export","inactive","flush"))){
      $fn = "urls_{$this->do}";
      return $this->$fn();
    }
    $where=""; 
    $filter="id";
    $order="";
    $asc=FALSE;       
    $perpage = "";
    // Reset Limit
    if(isset($_GET["perpage"]) && in_array($_GET["perpage"], array("25","50", "100"))) {
      $this->limit = Main::clean($_GET["perpage"], TRUE, 3);
      $perpage = $this->limit;
    }
    // Filters
    if(isset($_GET["filter"]) && in_array($_GET["filter"], array("most","less","old","anon"))){
        if($_GET["filter"]=="most"){
          $filter="click";
          $order="most";
          $asc=FALSE;
        }elseif($_GET["filter"]=="less"){
          $filter="click";
          $order="less";
          $asc=TRUE;
        }elseif($_GET["filter"]=="old"){
          $filter="date";
          $order="old";
          $asc=TRUE;
        }elseif($_GET["filter"]=="anon"){
          $order="anon";
          $where=array("userid"=>0);
        }
    }
    // Get User Info
    if($this->do=="view" && is_numeric($this->id)){
      $where=array("userid"=>$this->id);
    }
    // Get urls from Database
    $urls=$this->db->get("url",$where,array("order"=>$filter,"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","asc"=>$asc));

    if(empty($urls)) Main::redirect("admin/",array("danger","No URLs found."));  

    if(!empty($this->id)){
      $pagination=Main::nextpagination($this->page, Main::ahref("urls/view/{$this->id}")."?page=%d&filter=$order", ($this->db->rowCountAll < $this->limit ? TRUE : FALSE));
    }else{
      $pagination=Main::nextpagination($this->page, Main::ahref("urls")."?page=%d&filter=$order&perpage=$perpage", ($this->db->rowCountAll < $this->limit ? TRUE : FALSE));
    }

    // Set Header
    Main::set("title","网址管理");    
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Edit URL
       * @since 4.0
       **/
      private function urls_edit(){
        // Save Changes
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }
          // Validate URl
          if(empty($_POST["url"]) || !Main::is_url(Main::clean($_POST["url"],3))) return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("danger","Please enter a valid URL."));
          // Encode Geo Data
          $countries='';
          if(!empty($_POST['location'][0]) && !empty($_POST['target'][0])){
            foreach ($_POST['location'] as $i => $country) {
              if(!empty($country) && !empty($_POST['target'][$i]) && Main::is_url($_POST['target'][$i])){
                $countries[strtolower(Main::clean($country,3))]=Main::clean($_POST['target'][$i],3);
              }            
            }
            $countries=json_encode($countries);            
          }
          // Prepare Data
          $data = array(
            ":url" => Main::clean($_POST["url"],3),
            ":meta_title" => Main::clean($_POST["meta_title"],3),
            ":meta_description" => Main::clean($_POST["meta_description"],3),
            ":location" => $countries,
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0"
            );
          $this->db->update("url","",array("id"=>$this->id),$data);
          return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("success","URL已更新"));
        }

        // Get URL Info
        if(!$url=$this->db->get("url",array("id"=>"?"),array("limit"=>1),array($this->id))){
          Main::redirect(Main::ahref("urls","",TRUE),array("danger","This URL doesn't exist."));
        }
        $this->url_chart($url->alias.$url->custom);
        $beforehead="<div class='panel panel-default panel-dark'>     
                      <div class='panel-body'>
                        <div id='user-chart' class='chart' style='width:98%'></div>  
                      </div>
                    </div>";
        $beforehead.="<div class='form-group country hide' style='display:none'>
                    <div class='col-sm-6'>
                      <label>国家</label>
                        <select name='location[]'>
                          ".Main::countries()."
                        </select>
                    </div>
                    <div class='col-sm-6'>
                    <label>网址</label>
                      <input type='text' class='form-control' name='target[]' id='meta_description' value=''>                          
                    </div>
                  </div>";                    
        $header="编辑网址";
        $content="
        <p class='alert alert-info'>
          网址缩短时间 <strong>".Main::timeago($url->date)."</strong>点击了<strong>{$url->click}</strong> 次. 此网址 <strong>".(empty($url->location) ? "没有地址位置设置" : "有地理位置设置")."</strong>, <strong>".(empty($url->pass) ? "没有密码保护" : "有密码保护")."</strong> 及 <strong>".($url->userid ? "注册用户" : "匿名用户")."</strong>.
          </p>        
        <form action='".Main::ahref("urls/edit/{$url->id}")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='url' class='col-sm-3 control-label'>长网址</label>
            <div class='col-sm-9'>
              <input type='url' class='form-control' name='url' id='url' value='{$url->url}'>
            </div>
          </div>  

          <div class='form-group'>
            <label for='alias' class='col-sm-3 control-label'>短网址</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='alias' id='alias' value='{$url->alias}' disabled>
              <p class='help-block'>短网址.</p>
            </div>
          </div>  

          <div class='form-group'>
            <label for='custom' class='col-sm-3 control-label'>自定义别名</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' id='custom' value='{$url->custom}' disabled>
              <p class='help-block'>无法更改自定义别名.</p>
            </div>
          </div> 

          <div class='form-group'>
            <label for='meta_title' class='col-sm-3 control-label'>网页标题</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='meta_title' id='meta_title' value='{$url->meta_title}'>
            </div>
          </div>  

          <div class='form-group'>
            <label for='meta_description' class='col-sm-3 control-label'>网页内容</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='meta_description' id='meta_description' value='{$url->meta_description}'>
            </div>
          </div>
          <hr>
          <h4>地理位置数据 <a href='#' class='btn btn-primary btn-xs pull-right add_geo'>添加</a></h4>
          <div id='geo'>
          <p class='small alert alert-info'>单击“删除”后，地理目标数据将被删除，但仍将保留在数据库中。单击“提交”后，它将被永久删除。如果您改变主意或意外单击了“删除”，只需刷新此页面而不单击“提交”.</p>";

          if(!empty($url->location)){
            $geo=json_decode($url->location);
            foreach ($geo as $country => $link){
              $content.="<div class='form-group'>
                          <div class='col-sm-6'>
                            <label>国家</label>
                              <select name='location[]'>
                                ".Main::countries($country)."
                              </select>
                          </div>
                          <div class='col-sm-6'>
                          <label>网址</label>
                            <input type='text' class='form-control' name='target[]' id='meta_description' value='$link'>                          
                          </div>
                        </div><p><a href='#' class='btn btn-danger btn-xs delete_geo'>删除</a></p>";
            }
          } 
        $content.="</div><hr>
        <ul class='form_opt' data-id='ads'>
          <li class='text-label'>广告显示 <small>禁用此选项将不会显示此短URL的任何广告。</small></li>
          <li><a href='' class='last".(!$url->ads?' current':'')."' data-value='0'>禁用</a></li>
          <li><a href='' class='first".($url->ads?' current':'')."' data-value='1'>启用</a></li>
        </ul>
        <input type='hidden' name='ads' id='ads' value='".$url->ads."' />

        <ul class='form_opt' data-id='public'>
          <li class='text-label'>网址模式 <small>将此URL设为私有将使除管理员和创建者之外的所有人都无法访问统计信息.</small></li>
          <li><a href='' class='last".(!$url->public?' current':'')."' data-value='0'>私人</a></li>
          <li><a href='' class='first".($url->public?' current':'')."' data-value='1'>公开</a></li>
        </ul>
        <input type='hidden' name='public' id='public' value='".$url->public."' />             
        ".Main::csrf_token(TRUE)."
        <input type='submit' value='更新网址' class='btn btn-primary' />
        <a href='{$this->url}/urls/delete/{$url->id}' class='btn btn-danger delete'>删除</a>";
        if($url->userid){
          $content.="<a href='{$this->url}/users/edit/{$url->userid}' class='btn btn-success pull-right'>查看用户</a>";
        }
        $content.="</form>";
        Main::set("title","编辑网址");
        $this->header();
        include($this->t("edit"));
        $this->footer();        
      }
      /**
       * Delete URL
       * @since 5.0
       **/
      private function urls_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
        // Mass Delete URLs
        if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){
          
          // Validate Token
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("urls","",FALSE),array("danger",e("Invalid token. Please try again.")));
          }     

          $query = "(";
          $query2 =" (";
          $c = count($_POST["delete-id"]);
          $p = [];
          $i = 1;
          foreach ($_POST["delete-id"] as $id) {
            if($i >= $c){
              $query.="(`alias` = :id$i OR `custom`= :id$i)";
              $query2.="`short` = :id$i";
            }else{
              $query.="(`alias` = :id$i OR `custom`= :id$i) OR ";
              $query2.="`short` = :id$i OR ";
            }            
            $p[':id'.$i] = $id;
            $i++;
          }  
          $query .= ")";
          $query2 .= ")";

          $this->db->delete("url", $query, $p);
          $this->db->delete("stats", $query2, $p);
          return Main::redirect(Main::ahref("urls","",FALSE),array("success",e("Selected URLs have been deleted.")));
        }        
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Nonce
          if(!Main::validate_nonce("delete_url-{$this->id}")) return Main::redirect(Main::ahref("urls","",FALSE),array("danger","Security token expired. Please try again."));

          $url=$this->db->get("url",array("id"=>"?"),array("limit"=>1),array($this->id));
          $this->db->delete("url",array("id"=>"?"),array($this->id));
          $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
          return Main::redirect(Main::ahref("urls","",FALSE),array("success",e("网址删除成功.")));
        } 
        return Main::redirect(Main::ahref("urls","",FALSE),array("danger",e("An unexpected error occurred.")));          
      }
    /**
     *  URL Chart Data Function
     *  Generate data and inject it into the homepage. Also append the flot library.
     *  @since 4.0
     */   
      protected function url_chart($id,$span=25){
        $this->db->object=FALSE;
        $clicks=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= CURDATE() - INTERVAL $span DAY) AND short='{$id}'",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));
       
        $this->db->object=TRUE;

        $new_clicks=array(); 
        foreach ($clicks as $clicks[0] => $data) {
          $new_clicks[date("d M",strtotime($data["date"]))]=$data["count"];
        }        
        $timestamp = time();
        for ($i = 0 ; $i < $span ; $i++) {
            $array[date('d M', $timestamp)]=0;
            $timestamp -= 24 * 3600;
        }
         $date2=""; $var2=""; $i=0; 

        foreach ($array as $key => $value) {
          $i++;
          if(isset($new_clicks[$key])){
            $var2.="[".($span-$i).", ".$new_clicks[$key]."], ";
            $date2.="[".($span-$i).",\"$key\"], ";
          }else{
            $var2.="[".($span-$i).", 0], ";
            $date2.="[".($span-$i).", \"$key\"], ";
          }             
        }
        $data=array("clicks"=>array($var2,$date2));
        Main::admin_add("{$this->config["url"]}/static/js/flot.js","script",0);
        Main::admin_add("<script type='text/javascript'>var options = {
              series: {
                lines: { show: true, lineWidth: 2,fill: true},
                //bars: { show: true,lineWidth: 1 },  
                points: { show: true, lineWidth: 2 }, 
                shadowSize: 0
              },
              grid: { hoverable: true, clickable: true, tickColor: 'transparent', borderWidth:0 },
              colors: ['#0da1f5', '#1ABC9C','#F11010'],
              xaxis: {ticks:[{$data["clicks"][1]}], tickDecimals: 0, color: '#999'},
              yaxis: {ticks:3, tickDecimals: 0, color: '#CFD2E0'},
              xaxes: [ { mode: 'time'} ]
          }; 
          var data = [{
              label: ' 点击',
              data: [{$data["clicks"][0]}]
          }];
          $.plot('#user-chart', data ,options);</script>",'custom',TRUE);        
      } 
    /**
     * Export User
     * @since v3.0
     */   
    private function urls_export(){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename=URL_Shortener_URLList.csv');
      $result = $this->db->get("url","",array("order"=>"id","all"=>1));
      echo "Short URL, Long URL, Date, Clicks, User ID\n";
      foreach ($result as $line) {
        echo "{$this->config["url"]}/{$line->alias}{$line->custom},{$line->url},{$line->date},{$line->click},{$line->userid}\n";
      }
      return;
    }
   /**
    * Delete Inactive URLs
    * @since v3.0
    */  
    private function urls_inactive($clicks='0',$days='30'){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));   
      if(Main::validate_nonce("inactive_urls")){
        $a = $this->db->get(array("count"=>"alias,custom","table"=>"url"),"click='$clicks' AND date < (CURDATE() - INTERVAL $days DAY)",array("all"=>TRUE));
        if(!empty($a)){
          $list="";
          $i=0;
          $count=count($a);
          foreach ($a as $v) {
            $i++;         
            if(!empty($v->custom)){
              $list.="short='{$v->custom}'";
            }else{
              $list.="short='{$v->alias}'";
            }
            if($i<$count) $list.=" AND ";
          }
          $this->db->delete('url',"click='$clicks' AND date<(CURDATE() - INTERVAL $days DAY)");
          $this->db->delete('stats',"($list)");       
        } 
        Main::redirect(Main::ahref("urls","",FALSE),array("success","已从数据库中删除不活跃的网址."));
        return;
      }else{
        Main::redirect(Main::ahref("urls","",FALSE),array("danger","An error has occurred."));
        return;     
      }   
    } 
   /**
    * Delete Anonymous URLs
    * @since v4.2
    */  
    private function urls_flush(){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));   

      if(Main::validate_nonce("flush")){
        $a=$this->db->get(array("count"=>"alias,custom","table"=>"url"),array("userid" => "0") ,array("all"=>TRUE));
        if(!empty($a)){
          $list="";
          $i=0;
          $count=count($a);
          foreach ($a as $v) {
            $i++;         
            if(!empty($v->custom)){
              $list.="short='{$v->custom}'";
            }else{
              $list.="short='{$v->alias}'";
            }
            if($i<$count) $list.=" AND ";
          }
          $this->db->delete('url', array("userid" => "0"));
          $this->db->delete('stats',"($list)");       
        } 
        Main::redirect(Main::ahref("urls","",FALSE),array("success","已删除所有匿名用户的短网址."));
        return;
      }else{
        Main::redirect(Main::ahref("urls","",FALSE),array("danger","An error has occurred."));
        return;     
      }   
    }     
  /**
   * Ads
   * @since 5.3
   **/
  protected function ads(){
    // Toggle
    if(in_array($this->do, array("edit","delete","add"))){
      $fn = "ads_{$this->do}";
      return $this->$fn();
    }       
    if(isset($_GET["filter"]) && in_array($_GET["filter"], array("impression", "enabled", "type"))){
      $order = array("order"=> $_GET["filter"]);
    }else{
      $order = array("order"=>"id");
    }
    $ads = $this->db->get("ads","",$order);
    $count = $this->db->rowCountAll;
    Main::set("title","广告管理");
    $this->header();
    include($this->t("ads"));
    $this->footer();
  }
  /**
   * Add Ad
   * @since 5.3
   **/
  private function ads_add(){
    // Process Data
    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Results
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("ads/add","",FALSE),array("danger","Something went wrong, please try again."));
      }

      if(!empty($_POST["code"])){
        // Prepare Data
        $data = array(
          ":name" => Main::clean($_POST["name"],3),
          ":code" => $_POST["code"],
          ":type" => ad_type($_POST["type"]) ? $_POST["type"] : "728",
          ":enabled" => in_array($_POST["enabled"],array("0","1")) ? Main::clean($_POST["enabled"],3):"0"
          );         

        $this->db->insert("ads",$data);
        return Main::redirect(Main::ahref("ads","",FALSE),array("success","广告添加成功."));        
      }
      Main::redirect(Main::ahref("ads/add","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
    }
  
    $header="新建广告";
    $content="       
    <form action='".Main::ahref("ads/add")."' method='post' class='form-horizontal' role='form'>
      <div class='form-group'>
        <label for='title' class='col-sm-3 control-label'>广告名称</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='name' id='title' value=''>
        </div>
      </div> 
      <div class='form-group'>
        <label for='type' class='col-sm-3 control-label'>广告类型</label>
        <div class='col-sm-9'>
          <select name='type'>
            <option value='728'>728x90</option>
            <option value='468'>468x60</option>
            <option value='300'>300x250</option>
            <option value='resp'>浮窗</option>
            <option value='splash'>点击页面</option>
            <option value='frame'>框架广告</option>
          </select>
        </div>
      </div>              
      <div class='form-group'>
        <label for='code' class='col-sm-3 control-label'>广告代码</label>
        <div class='col-sm-9'>
          <textarea class='form-control' id='code' name='code' rows='10'></textarea>
        </div>
      </div>                          
      <hr />
      <ul class='form_opt' data-id='enabled'>
        <li class='text-label'>启用广告<small>是否要启用此广告？</small></li>
        <li><a href='' class='last' data-value='0'>否</a></li>
        <li><a href='' class='first current' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='enabled' id='enabled' value='1' />  

      ".Main::csrf_token(TRUE)."
      <input type='submit' value='添加广告' class='btn btn-primary' />";

    $content.="</form>";
    Main::set("title","添加广告");
    $this->header();
    include($this->t("edit"));
    $this->footer();       
  }  
  /**
   * Edit Ad
   * @since 5.3
   **/
  private function ads_edit(){
    // Add User
    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Results
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("ads/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
      }

      if(!empty($_POST["code"])){
        // Prepare Data
        $data = array(
          ":name" => Main::clean($_POST["name"],3),
          ":code" => $_POST["code"],
          ":type" => ad_type($_POST["type"]) ? $_POST["type"] : "728",
          ":enabled" => in_array($_POST["enabled"],array("0","1")) ? Main::clean($_POST["enabled"],3):"0"
          );         
    
        $this->db->update("ads","",array("id"=>$this->id),$data);
        return Main::redirect(Main::ahref("ads/edit/{$this->id}","",FALSE),array("success","广告编辑成功."));        
      }
      return Main::redirect(Main::ahref("ads/edit/{$this->id}","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
    }
    if(!$ad=$this->db->get("ads",array("id"=>"?"),array("limit"=>1),array($this->id))){
      return Main::redirect(Main::ahref("ads","",FALSE),array("danger","Advertisment doesn't exist."));
    }
    // Add CDN Editor
    $header="编辑广告";
    $content="       
    <form action='".Main::ahref("ads/edit/{$this->id}")."' method='post' class='form-horizontal' role='form'>
      <div class='form-group'>
        <label for='name' class='col-sm-3 control-label'>广告名称</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='name' id='name' value='{$ad->name}'>
        </div>
      </div>  
      <div class='form-group'>
        <label for='type' class='col-sm-3 control-label'>广告类型</label>
        <div class='col-sm-9'>
          <select name='type'>
            <option value='728' ".($ad->type == "728" ? "selected" : "").">728x90</option>
            <option value='468' ".($ad->type == "468" ? "selected" : "").">468x60</option>
            <option value='300' ".($ad->type == "300" ? "selected" : "").">300x250</option>
            <option value='resp' ".($ad->type == "resp" ? "selected" : "").">定向</option>
            <option value='splash' ".($ad->type == "splash" ? "selected" : "").">点击</option>
            <option value='frame' ".($ad->type == "frame" ? "selected" : "").">框架</option>
          </select>
        </div>
      </div>    
      <div class='form-group'>
        <label for='code' class='col-sm-3 control-label'>广告代码</label>
        <div class='col-sm-9'>
          <textarea class='form-control' id='code' name='code' rows='10'>{$ad->code}</textarea>
        </div>
      </div>           
      <hr />
      <ul class='form_opt' data-id='enabled'>
        <li class='text-label'>启用广告<small>是否要启用此广告？?</small></li>
        <li><a href='' class='last".(!$ad->enabled?' current':'')."' data-value='0'>否</a></li>
        <li><a href='' class='first".($ad->enabled?' current':'')."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='enabled' id='enabled' value='{$ad->enabled}' />  
  
      ".Main::csrf_token(TRUE)."
      <input type='submit' value='编辑广告' class='btn btn-primary' />";

    $content.="</form>";
    Main::set("title","编辑广告");
    $this->header();
    include($this->t("edit"));
    $this->footer();       
  }
  /**
   * Delete Ad
   * @since 5.3
   **/
  private function ads_delete(){
    // Disable if demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));            
    // Delete single URL
    if(!empty($this->id) && is_numeric($this->id)){
      // Validated Single Nonce
      if(Main::validate_nonce("delete_ad-{$this->id}")){
        $this->db->delete("ads",array("id"=>"?"),array($this->id));
        return Main::redirect(Main::ahref("ads","",FALSE),array("success","广告删除成功."));
      }        
    } 
    return Main::redirect(Main::ahref("ads","",FALSE),array("danger","An unexpected error occurred."));          
  }
  /**
   * Users
   * @since 4.0
   **/
  protected function users($limit=""){
    // Toggle
    if(in_array($this->do, array("edit","delete","add","export","inactive"))){
      $fn = "users_{$this->do}";
      return $this->$fn();
    }    
    if(!empty($limit)) $this->limit=$limit;
    // Filters
    $where="";
    $filter="id";
    $order="";
    $asc=FALSE;    
    if(isset($_GET["filter"]) && in_array($_GET["filter"], array("old","admin"))){
        if($_GET["filter"]=="admin"){
          $filter="id";
          $order="admin";
          $where=array("admin"=>1);
        }elseif($_GET["filter"]=="old"){
          $filter="date";
          $order="old";
          $asc=TRUE;
        }
    }   
    // Get urls from Database
    $users=$this->db->get("user",$where,array("count"=>TRUE,"order"=>$filter,"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","asc"=>$asc));
    if($this->page > $this->db->rowCount) Main::redirect("admin/",array("danger","No Users found."));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     
    $count="({$this->db->rowCount})";
    $pagination=Main::pagination($max, $this->page, Main::ahref("users")."?page=%d&filter=$order");    
    Main::set("title","管理用户");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Add user
       * @since 4.0
       **/
      private function users_add(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Something went wrong, please try again."));
          }
          if(!empty($_POST["username"])){
            if(!Main::username($_POST["username"])) return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Please enter a valid username."));
            if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
              Main::redirect(Main::ahref("users/add","",FALSE),array("danger","This username has already been used."));
              return;
            }
          }          
          // Get User info
          if(empty($_POST["email"]) || !Main::email($_POST["email"])){
            return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Please enter a valid email"));
          }   
          if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
            Main::redirect(Main::ahref("users/add","",FALSE),array("danger","This email has already been used."));
            return;
          }          
          if(strlen($_POST["password"]) < 5) return Main::redirect(Main::ahref("user","",FALSE),array("danger","Password has to be at least 5  characters."));          
          // Prepare Data
          $data = array(
            ":email" => Main::clean($_POST["email"],3),
            ":username" => Main::clean($_POST["username"],3),
            ":password" => Main::encode($_POST["password"]),
            ":api" => Main::strrand(12),
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":admin" => in_array($_POST["admin"],array("0","1")) ? Main::clean($_POST["admin"],3):"0",
            ":active" => in_array($_POST["active"],array("0","1")) ? Main::clean($_POST["active"],3):"1",
            ":banned" => "0",
            ":splash_opt" => in_array($_POST["splash_opt"],array("0","1")) ? Main::clean($_POST["splash_opt"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0",
            ":date" => "NOW()",
            ":pro" =>  in_array($_POST["pro"],array("0","1")) ? Main::clean($_POST["pro"],3):"0",
            ":last_payment" => !empty($_POST["last_payment"]) ? Main::clean($_POST["last_payment"],3) : NULL,
            ":expiration" => !empty($_POST["expiration"]) ? Main::clean($_POST["expiration"],3) : NULL            
            );         

          $this->db->insert("user",$data);
          return Main::redirect(Main::ahref("users","",FALSE),array("success","添加用户成功."));
        }
             
        $plans = "";
        foreach ($this->db->get("plans",[],["order" => "price_monthly", "asc" => TRUE]) as $plan) {
          $plans .= "<option value='{$plan->id}'>{$plan->name} - {$plan->price_monthly}/month";
        }

        $header="添加用户";
        $content="       
        <form action='".Main::ahref("users/add")."' method='post' class='form-horizontal' role='form'>

          <div class='form-group'>
            <label for='username' class='col-sm-3 control-label'>用户名</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='username' id='username' value=''>
              <p class='help-block'>公共配置文件必须具有用户名才能显示.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='email' class='col-sm-3 control-label'>电子邮箱</label>
            <div class='col-sm-9'>
              <input type='email' class='form-control' name='email' id='email' value=''>
              <p class='help-block'>请确保该电子邮件有效.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='password' class='col-sm-3 control-label'>密码</label>
            <div class='col-sm-9'>
              <input type='password' class='form-control' name='password' id='password' value=''>
              <p class='help-block'>密码必须至少为5个字符.</p>
            </div>
          </div>
          <div class='form-group'>
            <label for='pro' class='col-sm-3 control-label'>会员计划</label>
            <div class='col-sm-9'>
              <select name='pro' id='pro'>
                <option value='1'>高级用户</option>
                <option value='0' selected>免费用户</option>
              </select>
              <p class='help-block'>会员等级.</p>
            </div>
          </div> 
          <div class='form-group'>
            <label for='plaid' class='col-sm-3 control-label'>会员等级</label>
            <div class='col-sm-9'>
              <select name='planid' id='planid'>
                ".$plans."
              </select>
              <p class='help-block'>选择会员等级!</p>
            </div>
          </div>           
          <div class='form-group'>
            <label for='last_payment' class='col-sm-3 control-label'>最后一次付款</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='last_payment' data-toggle='datetimepicker' id='last_payment' value=''>
              <p class='help-block'>此格式设置最后一个付款日期：YYYY-MM-DD（例如2014-04-01）</p>
            </div>
          </div> 
          <div class='form-group'>
            <label for='expiration' class='col-sm-3 control-label'>到期时间</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='expiration' data-toggle='datetimepicker' id='expiration' value=''>
              <p class='help-block'>此格式设置到期日期：YYYY-MM-DD（例如2014-04-01）</p>
            </div>
          </div>                       
          <hr />
          <ul class='form_opt' data-id='admin'>
            <li class='text-label'>用户状态<small>设置此用户是管理员还是普通用户?</small></li>
            <li><a href='' class='last current' data-value='0'>用户</a></li>
            <li><a href='' class='first' data-value='1'>管理员</a></li>
          </ul>
          <input type='hidden' name='admin' id='admin' value='0' />

          <ul class='form_opt' data-id='splash_opt'>
            <li class='text-label'>启用自定义广告 <small>用户将能够宣传其产品并鼓励交通流量.</small></li>
            <li><a href='' class='last current' data-value='0'>禁用</a></li>
            <li><a href='' class='first' data-value='1'>启用</a></li>
          </ul>
          <input type='hidden' name='splash_opt' id='splash_opt' value='0' />

          <ul class='form_opt' data-id='ads'>
            <li class='text-label'>该用户显示广告<small>默认情况下，所有用户都将看到广告.</small></li>
            <li><a href='' class='last' data-value='0'>禁用</a></li>
            <li><a href='' class='first current' data-value='1'>启用</a></li>
          </ul>
          <input type='hidden' name='ads' id='ads' value='1' />

          <ul class='form_opt' data-id='public'>
            <li class='text-label'>书签访问 <small>默认设置用户书签状态.</small></li>
            <li><a href='' class='last current' data-value='0'>私密</a></li>
            <li><a href='' class='first' data-value='1'>公开</a></li>
          </ul>
          <input type='hidden' name='public' id='public' value='0' />   

          ".Main::csrf_token(TRUE)."
          <input type='submit' value='添加用户' class='btn btn-primary' />";

        $content.="</form>";
        Main::set("title","添加用户");
        Main::cdn("datepicker", NULL, TRUE);
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }  
      /**
       * Edit user
       * @since 4.0
       **/
      private function users_edit(){
        // Save Changes
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }
          // Get User info
          $user=$this->db->get("user",array("id"=>"?"),array("limit"=>1),array($this->id));
          if($user->auth!="twitter" && (empty($_POST["email"]) || !Main::email($_POST["email"]))){
            Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Please enter a valid email."));
            return;
          }
          if($_POST["email"]!==$user->email){
            if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
              Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","This email has already been used. Please try again."));
              return;
            }
          }   
          if(!empty($_POST["username"]) && $_POST["username"]!==$user->username){
            if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
              Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","This username has already been used. Please try again."));
              return;
            }
          }
          // Prepare Data
          $data = array(
            ":email" => Main::clean($_POST["email"],3),
            ":username" => Main::clean($_POST["username"],3),
            ":api" => Main::clean($_POST["api"],3),
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":admin" => in_array($_POST["admin"],array("0","1")) ? Main::clean($_POST["admin"],3):"0",
            ":active" => in_array($_POST["active"],array("0","1")) ? Main::clean($_POST["active"],3):"1",
            ":banned" => in_array($_POST["banned"],array("0","1")) ? Main::clean($_POST["banned"],3):"0",
            ":splash_opt" => in_array($_POST["splash_opt"],array("0","1")) ? Main::clean($_POST["splash_opt"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0",
            ":pro" =>  in_array($_POST["pro"],array("0","1")) ? Main::clean($_POST["pro"],3):"0",
            ":planid" => Main::clean($_POST["planid"],3),
            ":last_payment" => Main::clean($_POST["last_payment"],3),
            ":expiration" => Main::clean($_POST["expiration"],3)
            );         
          if(!empty($_POST["password"])){
            if(strlen($_POST["password"]) < 5) return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Password has to be at least 5 characters."));
            $data[":password"]=Main::encode($_POST["password"]);
          }
          $this->db->update("user","",array("id"=>$this->id),$data);
         return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("success","编辑用户成功."));
        }

        // Get URL Info
        if(!$user=$this->db->get("user",array("id"=>"?"),array("limit"=>1),array($this->id))){
          Main::redirect(Main::ahref("users","",TRUE),array("danger","This user doesn't exist."));
        }                 

        $user->last_payment = date("Y-m-d", strtotime($user->last_payment));
        $user->expiration = date("Y-m-d", strtotime($user->expiration));
        
        $plans = "";
        foreach ($this->db->get("plans",[],["order" => "price_monthly", "asc" => TRUE]) as $plan) {
          $plans .= "<option value='{$plan->id}' ".($user->planid == $plan->id ? "selected" : "").">{$plan->name} - {$plan->price_monthly}/month";
        }

        if($this->config["demo"]) {
          $user->email = "Hidden to protected privacy";
          $user->api = "Hidden to protected privacy";
        }

        $header="编辑用户";
        $content="       
        <form action='".Main::ahref("users/edit/{$user->id}")."' method='post' class='form-horizontal' role='form'>
          ".($user->id==$this->user->id?"<p class='alert alert-warning'><strong>这是你的账号!</strong> 编辑密码或管理员状态时要小心，以防止自己锁定.</p>":"")."         
          ".(!empty($user->auth)?"<p class='alert alert-warning'>此用户已使用 ".ucfirst($user->auth)." 登录.</p>":"")."         
          <div class='form-group'>
            <label for='username' class='col-sm-3 control-label'>用户名</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='username' id='username' value='{$user->username}'>
              <p class='help-block'>公共配置文件必须具有用户名才能显示.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='email' class='col-sm-3 control-label'>电子邮件</label>
            <div class='col-sm-9'>
              <input type='email' class='form-control' name='email' id='email' value='{$user->email}'>
            </div>
          </div>  
          <div class='form-group'>
            <label for='password' class='col-sm-3 control-label'>密码</label>
            <div class='col-sm-9'>
              <input type='password' class='form-control' name='password' id='password' value=''>
              <p class='help-block'>将此字段留空以保留当前密码，否则密码必须至少为5个字符.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='pro' class='col-sm-3 control-label'>会员计划</label>
            <div class='col-sm-9'>
              <select name='pro' id='pro'>
                <option value='1' ".($user->pro?"selected":"").">高级用户</option>
                <option value='0' ".(!$user->pro?"selected":"").">免费用户</option>
              </select>
            </div>
          </div> 
          <div class='form-group'>
            <label for='planid' class='col-sm-3 control-label'>会员等级</label>
            <div class='col-sm-9'>
              <select name='planid' id='planid'>
                {$plans}
              </select>
              <p class='help-block'>选择会员等级!</p>
            </div>
          </div>          
          <div class='form-group'>
            <label for='last_payment' class='col-sm-3 control-label'>最后一次付款</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' data-toggle='datetimepicker' name='last_payment' id='last_payment' value='{$user->last_payment}'>
              <p class='help-block'>免费会员的日期将是几个零.</p>
            </div>
          </div> 
          <div class='form-group'>
            <label for='expiration' class='col-sm-3 control-label'>到期时间</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' data-toggle='datetimepicker' name='expiration' id='expiration' value='{$user->expiration}'>
              <p class='help-block'>免费会员的日期将是几个零.</p>
            </div>
          </div>                               
          <div class='form-group'>
            <label for='api' class='col-sm-3 control-label'>API Key</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='api' id='api' value='{$user->api}'>
              <p class='help-block'>API密钥允许用户缩短其自己的应用或网站的网址。删除API密钥以阻止此用户使用API​​功能.</p>
            </div>
          </div>           
          <hr />
          <ul class='form_opt' data-id='admin'>
            <li class='text-label'>用户状态<small>此用户是管理员还是普通用户?</small></li>
            <li><a href='' class='last".(!$user->admin?' current':'')."' data-value='0'>用户</a></li>
            <li><a href='' class='first".($user->admin?' current':'')."' data-value='1'>管理员</a></li>
          </ul>
          <input type='hidden' name='admin' id='admin' value='".$user->admin."' />

          <ul class='form_opt' data-id='active'>
            <li class='text-label'>禁止登录 <small>禁止用户登录，但他的短网址仍然有效.</small></li>
            <li><a href='' class='last".(!$user->active?' current':'')."' data-value='0'>禁用</a></li>
            <li><a href='' class='first".($user->active?' current':'')."' data-value='1'>活跃</a></li>
          </ul>
          <input type='hidden' name='active' id='active' value='".$user->active."' />

          <ul class='form_opt' data-id='banned'>
            <li class='text-label'>禁止访问 <small>禁止用户登录及所有短网址.</small></li>
            <li><a href='' class='last".(!$user->banned?' current':'')."' data-value='0'>活跃</a></li>
            <li><a href='' class='first".($user->banned?' current':'')."' data-value='1'>禁用</a></li>
          </ul>
          <input type='hidden' name='banned' id='banned' value='".$user->banned."' />
                              
          <ul class='form_opt' data-id='splash_opt'>
            <li class='text-label'>启用自定义启动广告 <small>启用自定义启动广告.</small></li>
            <li><a href='' class='last".(!$user->splash_opt?' current':'')."' data-value='0'>禁用</a></li>
            <li><a href='' class='first".($user->splash_opt?' current':'')."' data-value='1'>启用</a></li>
          </ul>
          <input type='hidden' name='splash_opt' id='splash_opt' value='".$user->splash_opt."' />

          <ul class='form_opt' data-id='ads'>
            <li class='text-label'>显示广告<small>默认情况下，所有用户都将看到广告.</small></li>
            <li><a href='' class='last".(!$user->ads?' current':'')."' data-value='0'>禁用</a></li>
            <li><a href='' class='first".($user->ads?' current':'')."' data-value='1'>启用</a></li>
          </ul>
          <input type='hidden' name='ads' id='ads' value='".$user->ads."' />

          <ul class='form_opt' data-id='public'>
            <li class='text-label'>书签访问 <small>默认设置用户书签状态.</small></li>
            <li><a href='' class='last".(!$user->public?' current':'')."' data-value='0'>私密</a></li>
            <li><a href='' class='first".($user->public?' current':'')."' data-value='1'>公开</a></li>
          </ul>
          <input type='hidden' name='public' id='public' value='".$user->public."' />   

          ".Main::csrf_token(TRUE)."
          <input type='submit' value='编辑用户' class='btn btn-primary' />
          <a href='{$this->url}/users/delete/{$user->id}' class='btn btn-danger delete'>删除</a>";

        $content.="</form>";
        Main::set("title","编辑用户");
        Main::cdn("datepicker", NULL, TRUE);
        $this->header();
        include($this->t("edit"));
        $this->footer();        
      }
      /**
       * Delete user(s)
       * @since 5.0.1
       **/
      private function users_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));        
        // Mass Delete Users without deleting URLs
        if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){
          // Validate Token
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users","",FALSE),array("danger",e("Invalid token. Please try again.")));
          }             
          $query="(";
          $c=count($_POST["delete-id"]);
          $p = [];
          $i=1;
          foreach ($_POST["delete-id"] as $id) {
            $this->db->update("url",array("userid"=>0),array("userid"=>"?"),array($id));
            if($i>=$c){
              $query.="`id` = :id$i";
            }else{
              $query.="`id` = :id$i OR ";
            }                   
            $p[":id$i"]=$id;
            $i++;
          }  
          $query.=")";
          if($query!=="()") $this->db->delete("user",$query,$p);
          return Main::redirect(Main::ahref("users","",FALSE),array("success",e("所选用户已被删除，但其短网址未被删除.")));
        }        
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Single Nonce
          if(Main::validate_nonce("delete_user-{$this->id}")){
            $this->db->delete("user",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("users","",FALSE),array("success",e("用户已被删除.")));
          }
          // Validated Single Nonce
          if(Main::validate_nonce("delete_user_all-{$this->id}")){
            $urls=$this->db->get("url",array("userid"=>"?"),array("limit"=>1),array($this->id));
            foreach ($url as $url) {
              $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
            }
            $this->db->delete("url",array("userid"=>"?"),array($this->id));
            $this->db->delete("user",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("users","",FALSE),array("success",e("已成功删除此用户及其关联的所有短网址.")));
          }          
        } 
        return Main::redirect(Main::ahref("users","",FALSE),array("danger",e("An unexpected error occurred.")));          
      } 
      /**
        * Delete Inactive Users
        * @since v3.0
        */    
      private function users_inactive(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));  
        if(Main::validate_nonce("inactive_users")){
          $this->db->delete('user',array("active"=>'0',"admin"=>'0'));
          Main::redirect(Main::ahref("users","",FALSE),array("success","已从数据库中删除非活动用户."));
          return;
        }else{
          Main::redirect(Main::ahref("users","",FALSE),array("danger","An error has occurred."));
          return;     
        }   
      }
      /**
       * Export User
       * @since v2.0
       */   
      protected function users_export(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo.")); 
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=URL_Shortener_UserList.csv');
        $result = $this->db->get("user","",array("order"=>"id","all"=>1));
        echo "Username (empty=none), Email, Registration Date, Auth Method (empty=system), Pro, Expiration\n";
        foreach ($result as $line) {
          echo "{$line->username},{$line->email},{$line->date},{$line->auth},{$line->pro},{$line->expiration}\n";
        }
        return;
      }  
  /**
   * [subscription description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function subscription(){
    
    if(!$this->isExtended()) return $this->setUpExtended();

    // Get urls from Database
    $this->db->object=TRUE;
    $subscriptions = $this->db->get("subscription",[],array("count"=>TRUE,"order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}"));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     

    $count="({$this->db->rowCount})";
    $pagination = Main::pagination($max, $this->page, Main::ahref("subscription")."?page=%d");    
    Main::set("title","Manage Subscriptions");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }           
  /**
   * [setUpExtended description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   */
  private function setUpExtended(){

    if(isset($_POST["token"])){

      $key = Main::clean($_POST["key"], "3", TRUE);

      $Resp = Main::gemCurl("https://cdn.gempixel.com/validator/",[
                      "data" => ["key" => $key, "url" => $this->config["url"]]
                ]);

      if(!$Resp || empty($Resp) || $Resp == "Failed"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is not valid. It is either for another item or has been disabled.")); 
      }elseif($Resp == "Wrong.Item"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is for another item. Please use a 某某短网址系统 extended license purchase code.")); 
      }elseif($Resp == "Wrong.License"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is for a standard license. Please use a 某某短网址系统 extended license purchase code.")); 
      }else{
        return $this->installExtended($Resp);
      }
      return Main::redirect("admin/subscription",array("danger","An error occured. Please try again.")); 
    }

    $header = "Set Up Extended Version - Enable Subscriptions";
    $content = "       
    <form action='".Main::ahref("subscription")."' method='post' class='form-horizontal' role='form'>
      <p>You will need an extended license to enable subscription and stripe payments as per Envato's license. You DO NOT need an extended license if you are using this for yourself, your company or using PayPal one-time payment. To enable subscriptions, enter your extended license key below to unlock. For more info on the license type, <a href='https://codecanyon.net/licenses/standard' target='_blank'>click here</a>.</p>
      <p>If for some reason it is not working and your extended license is valid, the validation server might not be responding. You can try again later or contact us. You can find your license key in the <a href='https://codecanyon.net/downloads'>Downloads</a> section of Codecanyon. Click on Download then on License Certificate.</p>
      <hr>
        <h4>Extended license features</h4>
        <ul>
          <li>Ability to charge customers periodically (automatically)</li>
          <li>Enables stripe</li>
          <li>Enables invoicing</li>
          <li>Automatic payment management</li>
        </ul>
      <hr>
      <div class='form-group'>
        <label for='key' class='col-sm-3 control-label'>Extended License Key</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='key' id='key' value=''>
        </div>
      </div>  
      ".Main::csrf_token(TRUE)."
      <input type='submit' value='Enable Subscriptions' class='btn btn-primary' />
      </form>";

    Main::set("title","Set Up Extended");    
    $this->header();
    include($this->t("edit"));
    $this->footer();       
  }  
  /**
   * Payments
   * @since 4.0
   **/
  protected function payments(){
    // Export
    if($this->do=="export"){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo.")); 
      // Validated Nonce
      if(!Main::validate_nonce("export")) return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
      // Export Payments
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename=URL_Shortener_Payments.csv');
      $this->db->object=TRUE;
      $payments = $this->db->get("payment","",array("order"=>"id","all"=>1));
      echo "Transaction ID,Paypal Transaction ID,Status,User ID,Date,Membership Expiration,Amount\n";
      foreach ($payments as $payment) {
        echo "{$payment->id},{$payment->tid},{$payment->status},{$payment->userid},{$payment->date},{$payment->expiry},{$payment->amount}\n";
      }
      exit;
      return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
    }
    // Make a payment as Completed
    if($this->do=="review" && is_numeric($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      if($this->db->update("payment",array("status"=>"?"),array("id"=>"?"),array("Completed",$this->id))){
        return Main::redirect(Main::ahref("payments","",FALSE),array("success","付款已标记为已完成."));
      }
      return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
    }    
    if($this->do=="view" && is_numeric($this->id)){
      $where=array("userid"=>$this->id);
    }else{
      $where="";
    }    
    // Get urls from Database
    $this->db->object=TRUE;
    $payments=$this->db->get("payment",$where,array("count"=>TRUE,"order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}"));
    if($this->page > $this->db->rowCount) Main::redirect("admin/",array("danger","No payments found."));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     
    $count="({$this->db->rowCount})";
    $pagination=Main::pagination($max, $this->page, Main::ahref("payments")."?page=%d");    
    Main::set("title","支付流水");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Edit payment
       * @since 4.0
       **/
      private function payment_delete(){

      }  
  /**
   * [plans description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function plans(){
    // Toggle
    if(in_array($this->do, array("edit","delete","add"))){
      $fn = "plans_{$this->do}";
      return $this->$fn();
    }       
    $plans = $this->db->get("plans","",array("order" => "price_monthly"));
    $count = $this->db->rowCountAll;
    Main::set("title","管理会员计划");
    $this->header();
    include($this->t("plans"));
    $this->footer();
  }   
  /**
   * [plan_add description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function plans_add(){
    // Add Plan
    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Results
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("plans/add","",FALSE),array("danger","Something went wrong, please try again."));
      }

      $error = "";

      if(empty($_POST["name"])) {
        $error .= "<p>请输入有效的名称</p>";
      }
      Main::save("name", $_POST["name"]);
      Main::save("description", $_POST["description"]);
      Main::save("numurls", $_POST["numurls"]);
      Main::save("icon", $_POST["icon"]);
      Main::save("custom", $_POST["permission"]["custom"]);

      if($_POST["numurls"] == "" || !is_numeric($_POST["numurls"])){
        $error .= "<p>请输入有效的网址数目</p>";
      }
      Main::save("price_monthly", $_POST["price_monthly"]);

      if(!$_POST["free"] && (empty($_POST["price_monthly"]) || !is_numeric($_POST["price_monthly"]))){
        $error .= "<p>请输入有效价格，例如5.99</p>";
      }
      Main::save("price_monthly", $_POST["price_monthly"]);

      if(!$_POST["free"] && (empty($_POST["price_yearly"]) || !is_numeric($_POST["price_yearly"]))){
        $error .= "<p>请输入有效价格，例如59.99</p>";
      }   
      Main::save("price_yearly", $_POST["price_yearly"]);


      if($_POST["permission"]["splash"]["enabled"] && $_POST["permission"]["splash"]["count"] == "") {
        $error .= "<p>请输入允许创建的启动广告数量。不限使用0.</p>";
      }
      Main::save("permission-splash", $_POST["permission"]["splash"]["count"]);

      if($_POST["permission"]["overlay"]["enabled"] && $_POST["permission"]["overlay"]["count"] == "") {
        $error .= "<p>请输入允许创建的浮窗广告数量。不限使用0。</p>";
      }      
      Main::save("permission-overlay", $_POST["permission"]["overlay"]["count"]);

      if($_POST["permission"]["pixels"]["enabled"] && $_POST["permission"]["pixels"]["count"] == "") {
        $error .= "<p>请输入允许创建的来源跟踪数量。不限使用0。</p>";
      }     
      Main::save("permission-pixels", $_POST["permission"]["pixels"]["count"]);

      if($_POST["permission"]["domain"]["enabled"] && $_POST["permission"]["domain"]["count"] == "") {
        $error .= "<p>请输入允许添加自定义域名数量。不限使用0。</p>";
      }       
      Main::save("permission-domain", $_POST["permission"]["domain"]["count"]);

      if($this->db->get("plans", ["slug" => str_replace("-","",Main::slug(Main::clean($_POST["name"],3,TRUE)))], ["limit" => 1])){
        return Main::redirect(Main::ahref("plans/add","",FALSE),array("danger", "Plan already exists, choose a unique name."));
      }

      if(!empty($error)) return Main::redirect(Main::ahref("plans/add","",FALSE),array("danger", $error));           

      $data = [
          ":name" => Main::clean($_POST["name"],3,TRUE),
          ":slug" => str_replace("-","",Main::slug(Main::clean($_POST["name"],3,TRUE))),
          ":description" => Main::clean($_POST["description"],3,TRUE),
          ":icon" => Main::clean($_POST["icon"],3,TRUE),
          ":free" => Main::clean($_POST["free"],3,TRUE),
          ":numurls" => Main::clean($_POST["numurls"],3,TRUE),
          ":price_monthly" => $_POST["price_monthly"] ? Main::clean($_POST["price_monthly"],3,TRUE) : "0",
          ":price_yearly" => $_POST["price_yearly"] ? Main::clean($_POST["price_yearly"],3,TRUE) : "0",
          ":permission" => json_encode($_POST["permission"]),
          ":status" => Main::clean($_POST["status"],3,TRUE),
      ];

      if($_POST["free"] == "0" && isset($this->config["pt"]) && $this->config["pt"] == "stripe"){
        if(isset($this->config["stsk"]) && !empty($this->config["stsk"])){
          if(!$this->createPlan($data)){
            return Main::redirect(Main::ahref("plans","",FALSE),array("danger","An error occured with stripe. The plan was not created. Try again."));   
          }
        }
      }      

      $this->db->insert("plans", $data);
      Main::clear();
      return Main::redirect(Main::ahref("plans","",FALSE),array("success","会员计划添加成功"));  
    }

    $header = "添加计划";

    $content="       
    <form action='".Main::ahref("plans/add")."' method='post' class='form-horizontal' role='form'>
      <div class='form-group'>
        <label for='name' class='col-sm-3 control-label'>名称</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='name' id='name' value='".Main::get("name")."'>
          <p class='help-block'>会员等级.</p>   
        </div>
      </div>
      <div class='form-group'>
        <label for='description' class='col-sm-3 control-label'>等级介绍 (可选)</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='description' id='description' value='".Main::get("description")."'>
          <p class='help-block'>会员等级简单介绍</p>   
        </div>
      </div> 
      <div class='form-group'>
        <label for='icon' class='col-sm-3 control-label'>等级标志 (可选)</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='icon' id='icon' value='".Main::get("icon")."'>
          <p class='help-block'>此字段允许您为图标设置类。例如，如果要使用fontawome，请将库添加到主题文件中，并在此处使用类名，例如fa fa plus</p>   
        </div>
      </div>           
      <div class='form-group'>
        <label for='numurls' class='col-sm-3 control-label'>网址数量</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='numurls' id='numurls' value='".Main::get("numurls")."'>
          <p class='help-block'>这将限制用户可以缩短网址数量。”0'表示无限制。</p>   
        </div>
      </div>              
      <hr>
      <ul class='form_opt' data-id='free'>
        <li class='text-label'>免费用户 <small>这将是一个免费的计划。如果你没有免费的计划，用户将被迫升级.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='free' id='free' value='0' /> 

      <div class='form-group'>
        <label for='price_monthly' class='col-sm-3 control-label'>月付价格 (".$this->config["currency"].")</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='price_monthly' id='price_monthly' value='".Main::get("price_monthly")."'> 
          <p class='help-block'>例如 5.99</p>       
        </div>
      </div>
      <div class='form-group'>
        <label for='price_yearly' class='col-sm-3 control-label'>年付价格 (".$this->config["currency"].")</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='price_yearly' id='price_yearly' value='".Main::get("price_yearly")."'>  
          <p class='help-block'>例如 59.99</p>      
        </div>
      </div>      
      
      <hr>

      <ul class='form_opt' data-id='permission-splash-enabled'>
        <li class='text-label'>启动广告 <small>允许用户自定义启动广告.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[splash][enabled]' id='permission-splash-enabled' value='0' /> 
      
      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[splash][count]' class='control-label'>启动广告数量</label>    
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[splash][count]' id='permission[splash][count]' value='".Main::get("permission-splash")."'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-overlay-enabled'>
        <li class='text-label'>浮窗广告<small> 允许用户自定义浮窗广告.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[overlay][enabled]' id='permission-overlay-enabled' value='".Main::get("permission-overlay")."' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[overlay][count]' class='control-label'>浮窗广告数量</label>
          <p class='help-block'>0为不限制.</p>              
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[overlay][count]' id='permission[overlay][count]' value='".Main::get("permission-overlay")."'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-pixels-enabled'>
        <li class='text-label'>来源跟踪<small> 允许用户自定义来源跟踪.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[pixels][enabled]' id='permission-pixels-enabled' value='0' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[pixels][count]' class='control-label'>来源跟踪数量</label>
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[pixels][count]' id='permission[pixels][count]' value='".Main::get("permission-pixels")."'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-domain-enabled'>
        <li class='text-label'>自定义域名<small> 允许用户自定义启动域名.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[domain][enabled]' id='permission-domain-enabled' value='0' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[domain][count]' class='control-label'>自定义域名数量</label>
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[domain][count]' id='permission[domain][count]' value='".Main::get("permission-domain")."'>
        </div>
      </div>
      
      <hr>
      <ul class='form_opt' data-id='permission-device-enabled'>
        <li class='text-label'>设备定位访问权限 <small>允许用户设置设备定位访问权限.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[device][enabled]' id='permission-device-enabled' value='0' /> 

      <ul class='form_opt' data-id='permission-geo-enabled'>
        <li class='text-label'>地理位置定位 <small>允许用户设置地理位置定位访问权限.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[geo][enabled]' id='permission-geo-enabled' value='0' /> 

      <ul class='form_opt' data-id='permission-api-enabled'>
        <li class='text-label'>开发API <small>允许用户使用API.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[api][enabled]' id='permission-api-enabled' value='0' />          
      
      <ul class='form_opt' data-id='permission-export-enabled'>
        <li class='text-label'>导出数据 <small>允许用户导出数据.</small></li>
        <li><a href='' class='last current' data-value='0'>否</a></li>
        <li><a href='' class='first' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[export][enabled]' id='permission-export-enabled' value='0' /> 

      <hr>

      <div class='form-group'>
        <label for='permission[custom]' class='col-sm-3 control-label'>自定义文字</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='permission[custom]' id='permission[custom]' value='".Main::get("custom")."'>
          <p class='help-block'>您可以使用此字段添加自定义功能，例如电话支持. </p>
        </div>
      </div>  

      <ul class='form_opt' data-id='status'>
        <li class='text-label'>计划状态 <small>您可以选择暂时禁用此计划.</small></li>
        <li><a href='' class='last' data-value='0'>禁用</a></li>
        <li><a href='' class='first current' data-value='1'>启用</a></li>
      </ul>
      <input type='hidden' name='status' id='status' value='1' /> 

      ".Main::csrf_token(TRUE)."
      <input type='submit' value='添加计划' class='btn btn-primary' />";

    $content.="</form>";
    Main::set("title","添加计划");
    $this->header();
    include($this->t("edit"));
    $this->footer(); 
  }  
  /**
   * [createPlan description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @param   [type] $data [description]
   * @return  [type]       [description]
   */
  protected function createPlan($data){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);
    
    try {
      $product = \Stripe\Product::create([
        "name" => $data[":name"],
        "type" => "service",
      ]);      
    } catch (Exception $e) {
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;
    }

    try {
      $planMonthly = \Stripe\Plan::create(array(
        "amount" => $data[":price_monthly"]*100,
        "interval" => "month",
        "nickname" => "{$data[":name"]} - Monthly",
        "product" => $product->id,           
        "currency" => strtolower($this->config["currency"]),
        "id" => $data[":slug"]."monthly"
      ));      
    } catch (Exception $e) {
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;      
    }

    try {
      $planYearly = \Stripe\Plan::create(array(
        "amount" => $data[":price_yearly"]*100,
        "interval" => "year",
        "nickname" => "{$data[":name"]} - Yearly",
        "product" => $product->id,            
        "currency" => strtolower($this->config["currency"]),
        "id" => $data[":slug"]."yearly"
      ));
      
    } catch (Exception $e) {
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;          
    }

    return TRUE;
  }

  /**
   * [getPlans description]
   * @author 某某 <https://www.domain.com>
   * @version 5.2
   * @return  [type] [description]
   */
  protected function getPlans(){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

    try {

      $planMonthly = \Stripe\Plan::retrieve("PUSmonthly");

    } catch (Exception $e) {
      
      if($e->getMessage() == "No such plan: PUSmonthly"){

        if(empty($this->config["pro_monthly"])) $this->config["pro_monthly"] = "5.99";

        $planMonthly = \Stripe\Plan::create(array(
            "amount" => $this->config["pro_monthly"]*100,
            "interval" => "month",
            "nickname" => "Premium Plan - Monthly",
            "product" => array(
              "name" => "Premium Membership - Monthly"
            ),            
            "currency" => strtolower($this->config["currency"]),
            "id" => "PUSmonthly"
          ));

      }
    }

    try {

      $planYearly = \Stripe\Plan::retrieve("PUSyearly");

    } catch (Exception $e) {
      
      if($e->getMessage() == "No such plan: PUSyearly"){
        
        if(empty($this->config["pro_yearly"])) $this->config["pro_monthly"] = "49.99";

        $planYearly = \Stripe\Plan::create(array(
            "amount" => $this->config["pro_yearly"]*100,
            "interval" => "year",
            "nickname" => "Premium Plan - Yearly",
            "product" => array(
              "name" => "Premium Membership - Yearly"
            ),                
            "currency" => strtolower($this->config["currency"]),
            "id" => "PUSyearly"
          ));

      }
    }    

    return \Stripe\Plan::all(array("limit" => 20));
  }  
  /**
   * [plan_edit description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function plans_edit(){

    if(!$plan = $this->db->get("plans", ["id" => $this->id], ["limit" => "1"])){
      return Main::redirect(Main::ahref("plans","",FALSE),array("danger","This plan does not exist."));
    }

    // Add Plan
    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Results
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("plans/edit/{$plan->id}","",FALSE),array("danger","Something went wrong, please try again."));
      }

      $error = "";

      if(empty($_POST["name"])) {
        $error .= "<p>请输入有效的名称</p>";
      }

      if($_POST["numurls"] == "" || !is_numeric($_POST["numurls"])){
        $error .= "<p>请输入有效的网址数目</p>";
      }

      if(!$_POST["free"] && (empty($_POST["price_monthly"]) || !is_numeric($_POST["price_monthly"]))){
        $error .= "<p>请输入有效价格，例如5.99</p>";
      }

      if(!$_POST["free"] && (empty($_POST["price_yearly"]) || !is_numeric($_POST["price_yearly"]))){
        $error .= "<p>请输入有效价格，例如59.99</p>";
      }   

      if($_POST["permission"]["splash"]["enabled"] && $_POST["permission"]["splash"]["count"] == "") {
        $error .= "<p>请输入允许创建的启动广告数量。不限使用0。</p>";
      }

      if($_POST["permission"]["overlay"]["enabled"] && $_POST["permission"]["overlay"]["count"] == "") {
        $error .= "<p>请输入允许创建的浮窗广告数量。不限使用0。</p>";
      }      

      if($_POST["permission"]["pixels"]["enabled"] && $_POST["permission"]["pixels"]["count"] == "") {
        $error .= "<p>请输入允许创建的来源跟踪数量。不限使用0。</p>";
      }     

      if($_POST["permission"]["domain"]["enabled"] && $_POST["permission"]["domain"]["count"] == "") {
        $error .= "<p>请输入允许添加自定义域名数量。不限使用0。</p>";
      }       

      if(!empty($error)) return Main::redirect(Main::ahref("plans/edit/{$plan->id}","",FALSE),array("danger", $error));           

      $data = [
          ":name" => Main::clean($_POST["name"],3,TRUE),
          ":description" => Main::clean($_POST["description"],3,TRUE),
          ":icon" => Main::clean($_POST["icon"],3,TRUE),
          ":free" => Main::clean($_POST["free"],3,TRUE),
          ":numurls" => Main::clean($_POST["numurls"],3,TRUE),
          ":price_monthly" => $_POST["price_monthly"] ? Main::clean($_POST["price_monthly"],3,TRUE) : "0",
          ":price_yearly" => $_POST["price_yearly"] ? Main::clean($_POST["price_yearly"],3,TRUE) : "0",
          ":permission" => json_encode($_POST["permission"]),
          ":status" => Main::clean($_POST["status"],3,TRUE),
      ];

      if($_POST["free"] == "0" && isset($this->config["pt"]) && $this->config["pt"] == "stripe"){
        if(isset($this->config["stsk"]) && !empty($this->config["stsk"])){
          if(!$this->updatePlan($plan)){
            return Main::redirect(Main::ahref("plans/edit/{$plan->id}","",FALSE),array("danger","An error occured with stripe. The plan was not created. Try again."));        
          }
        }
      } 

      $this->db->update("plans", "", ["id" => $plan->id], $data);
      Main::clear();
      return Main::redirect(Main::ahref("plans/edit/{$plan->id}","",FALSE),array("success","会员计划编辑成功"));  
    }

    $header = "编辑会员计划";

    $plan->permission = json_decode($plan->permission);

    $content="       
    <form action='".Main::ahref("plans/edit/{$plan->id}")."' method='post' class='form-horizontal' role='form'>
      <div class='form-group'>
        <label for='name' class='col-sm-3 control-label'>名称</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='name' id='name' value='{$plan->name}'>
          <p class='help-block'>会员等级名称</p>   
        </div>
      </div>
      <div class='form-group'>
        <label for='description' class='col-sm-3 control-label'>等级介绍 (可选)</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='description' id='description' value='{$plan->description}'>
          <p class='help-block'>会员等级简单介绍</p>   
        </div>
      </div>   
      <div class='form-group'>
        <label for='icon' class='col-sm-3 control-label'>等级标志 (可选)</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='icon' id='icon' value='{$plan->icon}'>
          <p class='help-block'>此字段允许您为图标设置类。例如，如果要使用fontawome，请将库添加到主题文件中，并在此处使用类名，例如fa fa plus</p>   
        </div>
      </div>      
      <div class='form-group'>
        <label for='numurls' class='col-sm-3 control-label'>网址数量</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='numurls' id='numurls' value='{$plan->numurls}'>
          <p class='help-block'>这将限制用户可以缩短网址数量。”0'表示无限制。</p>   
        </div>
      </div>              
      <hr>
      <ul class='form_opt' data-id='free'>
        <li class='text-label'>免费用户 <small>这将是一个免费的计划。如果你没有免费的计划，用户将被迫升级.</small></li>
        <li><a href='' class='last".(!$plan->free ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->free ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='free' id='free' value='{$plan->free}' /> 

      <div class='form-group'>
        <label for='price_monthly' class='col-sm-3 control-label'>月付价格 (".$this->config["currency"].")</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='price_monthly' id='price_monthly' value='{$plan->price_monthly}'> 
          <p class='help-block'>例如5.99 ".(isset($this->config["pt"]) && $this->config["pt"] == "stripe" ? "不允许随意更改计划的价格，而是将删除此计划（每月），并创建新计划，现有用户不会受到影响。" : "")."</p>       
        </div>
      </div>
      <div class='form-group'>
        <label for='price_yearly' class='col-sm-3 control-label'>年付价格 (".$this->config["currency"].")</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='price_yearly' id='price_yearly' value='{$plan->price_yearly}'>  
          <p class='help-block'>例如59.99 ".(isset($this->config["pt"]) && $this->config["pt"] == "stripe" ? "不允许随意更改计划的价格，而是将删除此计划（年付），并创建新计划，现有用户不会受到影响。" : "")."</p>      
        </div>
      </div>      
      
      <hr>

      <ul class='form_opt' data-id='permission-splash-enabled'>
        <li class='text-label'>启动广告 <small>允许用户自定义启动广告。.</small></li>
        <li><a href='' class='last".(!$plan->permission->splash->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->splash->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[splash][enabled]' id='permission-splash-enabled' value='{$plan->permission->splash->enabled}' /> 
      
      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[splash][count]' class='control-label'>启动广告数量</label>    
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[splash][count]' id='permission[splash][count]' value='{$plan->permission->splash->count}'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-overlay-enabled'>
        <li class='text-label'>浮窗广告<small> 允许用户自定义浮窗广告。.</small></li>
        <li><a href='' class='last".(!$plan->permission->overlay->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->overlay->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[overlay][enabled]' id='permission-overlay-enabled' value='{$plan->permission->overlay->enabled}' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[overlay][count]' class='control-label'>浮窗广告数量</label>
          <p class='help-block'>0为不限制.</p>              
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[overlay][count]' id='permission[overlay][count]' value='{$plan->permission->overlay->count}'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-pixels-enabled'>
        <li class='text-label'>来源跟踪<small> 允许用户自定义来源跟踪。.</small></li>
        <li><a href='' class='last".(!$plan->permission->pixels->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->pixels->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[pixels][enabled]' id='permission-pixels-enabled' value='{$plan->permission->pixels->enabled}' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[pixels][count]' class='control-label'>来源跟踪数量</label>
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[pixels][count]' id='permission[pixels][count]' value='{$plan->permission->pixels->count}'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-domain-enabled'>
        <li class='text-label'>自定义域名<small> 允许用户自定义启动域名.</small></li>
        <li><a href='' class='last".(!$plan->permission->domain->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->domain->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[domain][enabled]' id='permission-domain-enabled' value='{$plan->permission->domain->enabled}' /> 

      <div class='form-group'>
        <div class='col-md-8'>
          <label for='permission[domain][count]' class='control-label'>自定义域名数量</label>
          <p class='help-block'>0为不限制.</p>
        </div>              
        <div class='col-md-4'>
          <input type='text' class='form-control' name='permission[domain][count]' id='permission[domain][count]' value='{$plan->permission->domain->count}'>
        </div>
      </div>
      
      <hr>

      <ul class='form_opt' data-id='permission-device-enabled'>
        <li class='text-label'>设备定位访问权限 <small>允许用户设置设备定位访问权限.</small></li>
        <li><a href='' class='last".(!$plan->permission->device->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->device->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[device][enabled]' id='permission-device-enabled' value='{$plan->permission->device->enabled}' /> 

      <ul class='form_opt' data-id='permission-geo-enabled'>
        <li class='text-label'>地理定位 <small>允许用户设置地理位置定位访问权限。.</small></li>
        <li><a href='' class='last".(!$plan->permission->geo->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->geo->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[geo][enabled]' id='permission-geo-enabled' value='{$plan->permission->geo->enabled}' /> 

      <ul class='form_opt' data-id='permission-api-enabled'>
        <li class='text-label'>开发API <small>允许用户使用API.</small></li>
        <li><a href='' class='last".(!$plan->permission->api->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->api->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[api][enabled]' id='permission-api-enabled' value='{$plan->permission->api->enabled}' />          
      
      <ul class='form_opt' data-id='permission-export-enabled'>
        <li class='text-label'>导出数据 <small>允许用户导出数据.</small></li>
        <li><a href='' class='last".(!$plan->permission->export->enabled ? " current" : "")."' data-value='0'>否</a></li>
        <li><a href='' class='first".($plan->permission->export->enabled ? " current" : "")."' data-value='1'>是</a></li>
      </ul>
      <input type='hidden' name='permission[export][enabled]' id='permission-export-enabled' value='{$plan->permission->export->enabled}' /> 

      <hr>

      <div class='form-group'>
        <label for='permission[custom]' class='col-sm-3 control-label'>自定义文字</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='permission[custom]' id='permission[custom]' value='{$plan->permission->custom}'>
          <p class='help-block'>您可以使用此字段添加自定义功能，例如电话支持.</p>
        </div>
      </div>  

      <ul class='form_opt' data-id='status'>
        <li class='text-label'>计划状态 <small>您可以选择暂时禁用此计划.</small></li>
        <li><a href='' class='last".(!$plan->status ? " current" : "")."' data-value='0'>禁用</a></li>
        <li><a href='' class='first".($plan->status ? " current" : "")."' data-value='1'>启用</a></li>
      </ul>
      <input type='hidden' name='status' id='status' value='{$plan->status}' /> 

      ".Main::csrf_token(TRUE)."
      <input type='submit' value='编辑计划' class='btn btn-primary' />";

    $content.="</form>";
    Main::set("title","编辑计划");
    $this->header();
    include($this->t("edit"));
    $this->footer(); 
  }   
  /**
   * [updatePlan description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function updatePlan($plan){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

    // Price Changed
    if($_POST["price_monthly"] != $plan->price_monthly){
      $mPlan = \Stripe\Plan::retrieve($plan->slug."monthly");
      $productid = $mPlan->product;
      $mPlan->delete();
  
      try {
        $planMonthly = \Stripe\Plan::create(array(
          "amount" => $_POST["price_monthly"]*100,
          "interval" => "month",
          "nickname" => "{$_POST["name"]} - Monthly",
          "product" => $productid,           
          "currency" => strtolower($this->config["currency"]),
          "id" => $plan->slug."monthly"
        ));      
      } catch (Exception $e) {
        error_log("Stripe Error: {$e->getMessage()}");
        Main::redirect(Main::ahref("plans/edit/{$plan->id}", "", FALSE), ["danger", $e->getMessage()]);
        exit;      
      }                   
    }

    if($_POST["price_yearly"] != $plan->price_yearly){
      $YPlan = \Stripe\Plan::retrieve($plan->slug."yearly");
      $productid = $YPlan->product;
      $YPlan->delete();

      try {
        $planMonthly = \Stripe\Plan::create(array(
          "amount" => $_POST["price_yearly"]*100,
          "interval" => "month",
          "nickname" => "{$_POST["name"]} - Yearly",
          "product" => $productid,           
          "currency" => strtolower($this->config["currency"]),
          "id" => $plan->slug."yearly"
        ));      
      } catch (Exception $e) {
        error_log("Stripe Error: {$e->getMessage()}");
        Main::redirect(Main::ahref("plans/edit/{$plan->id}", "", FALSE), ["danger", $e->getMessage()]);
        exit;      
      }      
    }    
    return TRUE;
  }  
  /**
   * [plans_delete description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  [type] [description]
   */
  private function plans_delete(){
    // Disable if demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));            
    // Delete single URL
    if(!empty($this->id) && is_numeric($this->id)){
      // Validated Single Nonce
      if(Main::validate_nonce("delete_plan-{$this->id}")){
        if($plan = $this->db->get("plans", ["id" => "?"], ["limit" => 1], [$this->id])){

          if($plan->free == "0" && isset($this->config["pt"]) && $this->config["pt"] == "stripe"){
            if(isset($this->config["stsk"]) && !empty($this->config["stsk"])){
              if(!$this->deletePlan($plan)){
                return Main::redirect(Main::ahref("plans","",FALSE),array("danger","An error occured with stripe. The plan was not deleted. Try again."));        
              }
            }
          }           
          $this->db->delete("plans",array("id" => "?"),array($this->id));
          return Main::redirect(Main::ahref("plans","",FALSE),array("success",e("会员计划删除成功.")));
        }
      }        
    } 
    return Main::redirect(Main::ahref("plans","",FALSE),array("danger",e("An unexpected error occurred.")));          
  }    
  /**
   * [deletePlan description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @param   [type] $plan [description]
   * @return  [type]       [description]
   */
  protected function deletePlan($plan){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

    try {
      $mPlan = \Stripe\Plan::retrieve($plan->slug."monthly");
      $productid = $mPlan->product;
      $mPlan->delete();      
    } catch (Exception $e) {
      error_log("Stripe Error: {$e->getMessage()}");
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;      
    }
    try {
      $YPlan = \Stripe\Plan::retrieve($plan->slug."yearly");
      $productid = $YPlan->product;
      $YPlan->delete();    
    } catch (Exception $e) {
      error_log("Stripe Error: {$e->getMessage()}");
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;      
    }
    try {
      $Product = \Stripe\Product::retrieve($productid);      
      $Product->delete();         
    } catch (Exception $e) {
      error_log("Stripe Error: {$e->getMessage()}");
      Main::redirect(Main::ahref("plans", "", FALSE), ["danger", $e->getMessage()]);
      exit;         
    }
    return TRUE;    
  }  
  /**
   * Pages
   * @since 4.0
   **/
  protected function pages(){
    // Toggle
    if(in_array($this->do, array("edit","delete","add"))){
      $fn = "pages_{$this->do}";
      return $this->$fn();
    }       
    $pages=$this->db->get("page","",array("order"=>"id"));
    $count=$this->db->rowCountAll;
    Main::set("title","Manage Pages");
    $this->header();
    include($this->t("page"));
    $this->footer();
  }
      /**
       * Add page
       * @since 4.0
       **/
      private function pages_add(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","Something went wrong, please try again."));
          }

          if(!empty($_POST["name"]) && !empty($_POST["content"])){
            if($this->db->get("page",array("seo"=>Main::slug((!empty($_POST["slug"])?$_POST["seo"]:$_POST["name"]))))){
              Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","This slug is already taken, please use another one."));
            }
            // Prepare Data
            $data = array(
              ":name" => Main::clean($_POST["name"],3),
              ":seo" => empty($_POST["slug"]) ? Main::slug($_POST["name"]) : Main::slug($_POST["slug"]),
              ":content" => $_POST["content"],
              ":menu" => in_array($_POST["menu"],array("0","1")) ? Main::clean($_POST["menu"],3):"0"
              );         

            $this->db->insert("page",$data);
            return Main::redirect(Main::ahref("pages","",FALSE),array("success","页面添加成功."));        
          }
          Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
        }

        // Add CDN Editor
        Main::cdn("ckeditor","",1);
        Main::admin_add("<script>CKEDITOR.replace( 'editor', {height: 350});</script>","custom",1);
        $header="添加页面";
        $content="       
        <form action='".Main::ahref("pages/add")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='name' class='col-sm-3 control-label'>标题</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='name' id='name' value=''>
            </div>
          </div>  
          <div class='form-group'>
            <label for='seo' class='col-sm-3 control-label'>路径</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='slug' id='slug' value=''>
              <p class='help-block'>例如. {$this->config["url"]}/page/<strong>Slug</strong>. 保留为空将自动生成它.</p>
            </div>
          </div>
         <ul class='form_opt' data-id='menu'>
            <li class='text-label'>添加到菜单<small>是否要在菜单中添加指向此页的链接?</small></li>
            <li><a href='' class='last current' data-value='0'>否</a></li>
            <li><a href='' class='first' data-value='1'>是</a></li>
          </ul>
          <input type='hidden' name='menu' id='menu' value='0' />          
          
          <textarea class='form-control ckeditor' id='editor' name='content' rows='25'></textarea>
          <br>
          ".Main::csrf_token(TRUE)."
          <input type='submit' value='添加页面' class='btn btn-primary' />";

        $content.="</form>";
        Main::set("title","添加页面");
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }  
      /**
       * Edit page
       * @since 4.0
       **/
      private function pages_edit(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }

          if(!empty($_POST["name"])){
            if($this->db->get("page","seo=? AND id!=?","",array(Main::slug(!empty($_POST["slug"])?$_POST["seo"]:$_POST["name"]),$this->id))){
              Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","This slug is already taken, please use another one."));
            }
            // Prepare Data
            $data = array(
              ":name" => Main::clean($_POST["name"],3),
              ":seo" => empty($_POST["slug"]) ? Main::slug($_POST["name"]) : Main::slug($_POST["slug"]),
              ":content" => $_POST["content"],
              ":menu" => in_array($_POST["menu"],array("0","1")) ? Main::clean($_POST["menu"],3):"0"
              );         

            $this->db->update("page","",array("id"=>$this->id),$data);
            return Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("success","页面编辑成功."));        
          }
          Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
        }
        if(!$page=$this->db->get("page",array("id"=>"?"),array("limit"=>1),array($this->id))){
          return Main::redirect(Main::ahref("pages","",FALSE),array("danger","Page doesn't exist."));
        }
        // Add CDN Editor
        Main::cdn("ckeditor","",1);
        Main::admin_add("<script>CKEDITOR.replace( 'editor', {height: 350});</script>","custom",1);
        $header="编辑页面";
        $content="       
        <form action='".Main::ahref("pages/edit/{$this->id}")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='name' class='col-sm-3 control-label'>标题</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='name' id='name' value='{$page->name}'>
            </div>
          </div>  
          <div class='form-group'>
            <label for='seo' class='col-sm-3 control-label'>路径</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='slug' id='slug' value='{$page->seo}'>
              <p class='help-block'>例如 {$this->config["url"]}/page/<strong>Slug</strong>. 保留为空将自动生成它..</p>
            </div>
          </div>
         <ul class='form_opt' data-id='menu'>
            <li class='text-label'>添加到菜单<small>是否要在菜单中添加指向此页的链接?</small></li>
            <li><a href='' class='last".(!$page->menu?' current':'')."' data-value='0'>否</a></li>
            <li><a href='' class='first".($page->menu?' current':'')."' data-value='1'>是</a></li>
          </ul>
          <input type='hidden' name='menu' id='menu' value='0' />          
          
          <textarea class='form-control ckeditor' id='editor' name='content' rows='25'>{$page->content}</textarea>
          <br>
          ".Main::csrf_token(TRUE)."
          <input type='submit' value='编辑页面' class='btn btn-primary' />
          <a href='".Main::href("page/{$page->seo}")."' class='btn btn-success' target='_blank'> 查看页面</a>";

        $content.="</form>";
        Main::set("title","编辑页面");
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }
      /**
       * Delete page
       * @since 4.0
       **/
      private function pages_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));            
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Single Nonce
          if(Main::validate_nonce("delete_page-{$this->id}")){
            $this->db->delete("page",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("pages","",FALSE),array("success",e("页面删除成功.")));
          }        
        } 
        return Main::redirect(Main::ahref("pages","",FALSE),array("danger",e("An unexpected error occurred.")));          
      }   
  /**
   * Settings
   * @since 5.5
   **/
  protected function settings(){    
    // Update Settings
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("settings","",FALSE),array("danger","Something went wrong, please try again."));
      }         
      // Upload Logo
      if(isset($_FILES["logo_path"]) && !empty($_FILES["logo_path"]["tmp_name"])) {
        $ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");            
        if(!isset($ext[$_FILES["logo_path"]["type"]])) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Logo 必须是 PNG 或 JPEG文件.")));
        if($_FILES["logo_path"]["size"]>100*1024) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Logo 必须是 PNG 或 JPEG文件 (大于 100KB).")));            
        $_POST["logo"]="auto_site_logo.".$ext[$_FILES["logo_path"]["type"]];
        move_uploaded_file($_FILES["logo_path"]['tmp_name'], ROOT."/content/auto_site_logo.".$ext[$_FILES["logo_path"]["type"]]);                
      }
      // Delete Logo
      if(isset($_POST["remove_logo"])){
        unlink(ROOT."/content/".$this->config["logo"]);
        $_POST["logo"]="";
      }       
      
      // Upload Logo
      if(isset($_FILES["favicon_path"]) && !empty($_FILES["favicon_path"]["tmp_name"])) {
        $ext=array("image/png"=>"png","image/x-icon"=>"ico");      

        if(!isset($ext[$_FILES["favicon_path"]["type"]])) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Favicon 必须是 PNG 或 ICO.")));
        if($_FILES["favicon_path"]["size"]>100*1024) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Favicon 必须是 PNG 或 ICO (大于 100KB).")));            
        $_POST["favicon"]="favicon.".$ext[$_FILES["favicon_path"]["type"]];
        move_uploaded_file($_FILES["favicon_path"]['tmp_name'], ROOT."/content/favicon.".$ext[$_FILES["favicon_path"]["type"]]);                
      }
      // Delete Logo
      if(isset($_POST["remove_favicon"])){
        unlink(ROOT."/content/".$this->config["favicon"]);
        $_POST["favicon"]="";
      }        

      // Encode SMTP
      $_POST["smtp"] = json_encode($_POST["smtp"]);

      if(isset($_POST["alias_length"]) && $_POST["alias_length"] < 3){
        return Main::redirect(Main::ahref("settings","",FALSE),array("danger","别名长度必须至少为3."));
      }

      // Update Config
      foreach($_POST as $config => $var){
        if(in_array($config, array("ad728","ad300","ad468"))){
          $this->db->update("settings",array("var"=>"?"),array("config"=>"?"),array($var,$config));
        }else{
          $this->db->update("settings",array("var"=>"?"),array("config"=>"?"),array(Main::clean($var,2,TRUE),$config));
        }
      }

      return Main::redirect(Main::ahref("settings","",FALSE),array("success","设置成功.")); 
    } 


    $lang="<option value='' ".($this->config["default_lang"]==""?" selected":"").">English</option>";
    foreach (new RecursiveDirectoryIterator(ROOT."/includes/languages/") as $path){
      if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && $path->getFilename()!=="lang_sample.php" && $path->getFilename()!=="index.php" && Main::extension($path->getFilename())==".php"){  
          $data = token_get_all(file_get_contents($path));
          $data = $data[1][1];
          if(preg_match("~Language:\s(.*)~", $data,$name)){
            $name ="".strip_tags(trim($name[1]))."";
          }        
        $code = str_replace(".php", "" , $path->getFilename());
        $lang .= "<option value='".$code."' ".($this->config["default_lang"]==$code ? " selected":"").">$name</option>";
      }
    }        

    $this->config["email"] = ($this->config["demo"])?"Hidden" : $this->config["email"];
    $this->config["apikey"] = ($this->config["demo"])?"Hidden" : $this->config["apikey"];
    $this->config["safe_browsing"] = ($this->config["demo"])?"Hidden" : $this->config["safe_browsing"];
    $this->config["phish_api"] = ($this->config["demo"])?"Hidden" : $this->config["phish_api"];
    $this->config["captcha_public"] = ($this->config["demo"])?"Hidden" : $this->config["captcha_public"];
    $this->config["captcha_private"] = ($this->config["demo"])?"Hidden" : $this->config["captcha_private"];
    $this->config["facebook_secret"] = ($this->config["demo"])?"Hidden" : $this->config["facebook_secret"];
    $this->config["facebook_app_id"] = ($this->config["demo"])?"Hidden" : $this->config["facebook_app_id"];
    $this->config["twitter_key"] = ($this->config["demo"])?"Hidden" : $this->config["twitter_key"];
    $this->config["twitter_secret"] = ($this->config["demo"])?"Hidden" : $this->config["twitter_secret"];

    Main::admin_add("<style>.help-block{font-size:12px;color: #777777;font-weight: 400;}</style>","custom",FALSE);
    Main::cdn("tagsinput", NULL, TRUE);
    Main::admin_add("<script>
                      $(document).ready(function(){
                        $('#keyword_blacklist').tagsInput({'defaultText':'Add'});  
                        $('#domain_blacklist').tagsInput({'defaultText':'Add'});  
                        $('#schemes').tagsInput({'defaultText':'Add'});  
                      })
                    </script>","custom",TRUE);

    Main::set("title","网址设置");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
  /**
   * [tool description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function tools(){
    $fn = "tools_{$this->do}";
    if(method_exists(__CLASS__,$fn)){
      return $this->{$fn}();
    }
    Main::set("title","站长工具");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }

  /**
    * Optimize Database
    * @since 5.3
    */    
  public function tools_optimize(){
    // Disable this for demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}user");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}url");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}stats");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}settings");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}bundle");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}page");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}payment");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}splash");
    // $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}subscription");
    return Main::redirect(Main::ahref("tools","",FALSE),array("success","Database has been optimized. Your site should perform better now."));
  }   
  /**
   * Send Newsletter
   * @author 某某
   * @since  5.3
   */
  protected function tools_newsletter(){
    if(in_array($this->id, array("send"))){
      $fn = __FUNCTION__."_{$this->id}";
      return $this->$fn();
    }          
    $count = $this->db->count("user", "active = '1'");
    $beforehead = "<div class='panel panel-default panel-body panel-green stats'>
                      <p class='main-stats'><span>$count</span> 封电子邮件.</p>
                   </div>";
    $header = "向用户发送邮件";      
    $content = "
      <p>此工具可能非常耗费内存，因此您必须确保您的托管服务提供商支持此功能，或允许您一次发送许多电子邮件，否则很可能会让您遇到麻烦。另外请不要向用户发送垃圾邮件，否则他们会永久将您的域名列入黑名单。也不要发送太多新闻通讯，因为您的托管服务提供商会怀疑您是垃圾邮件.        
      </p>
      <hr>
      <h4>发送邮件</h4>
      <p>
        您可以向用户发送自定义消息，让他们了解更改或重要公告。只需在下面输入您的消息，然后按发送。您还可以使用一些短代码来添加动态数据.
      </p>
      <form class='form' role='form' action='".Main::ahref("tools/newsletter/send")."' method='post'>
        <div class='form-group'>
          <div class='row'>
            <div class='col-md-6'>
              <div class='form-group'>
                <label class='control-label'>邮件标题</label>
                <br><br>
                <input type='text' name='subject' class='form-control'>           
              </div>            
              <div class='form-group'>
                <label class='control-label'>邮件内容</label>
                <br><br>
                <textarea name='message' rows='10' id='editor' class='editor form-control'></textarea>                 
              </div>        
            </div>
            <div class='col-md-6'>
              <div class='form-group'>
                <label class='control-label'>相关字段</label>
                <br><br>
                <ul>
                  <li>用户: <strong>{username}</strong></li>
                  <li>邮箱: <strong>{email}</strong></li>
                  <li>注册时间: <strong>{date}</strong></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        ".Main::csrf_token(TRUE)."
        <button class='btn btn-primary'>发送邮件</button>          
      </form>        
    ";

    Main::cdn("summernote", NULL, TRUE);
    Main::admin_add("<script>$(document).ready(function() { $('#editor').summernote({height: 200,  toolbar: [
      ['style', ['bold', 'italic', 'underline', 'clear']],
      ['font', ['strikethrough', 'superscript', 'subscript']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['height', ['height']]
    ]}) });</script>","custom", FALSE);
    Main::set("title", $header);
    $this->header();
    include($this->t("edit"));
    $this->footer();      
  }
  /**
   * Send Custom Newsletter
   * @author 某某
   * @since  5.4
   */
  private function tools_newsletter_send(){
    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Token
      if(!Main::validate_csrf_token($_POST["token"])){
        Main::redirect(Main::ahref("tools/newsletter","",FALSE),array("danger","Invalid token, please try again."));
        return;
      }     
      // Check for empty email content      
      if(empty($_POST["subject"]) || empty($_POST["message"])) return Main::redirect(Main::ahref("tools/newsletter","",FALSE),array("danger","You are trying to send empty emails."));
      // Get Users
      $users = $this->db->get("user",array("active" => "1"));
      foreach ($users as $user) {
        if(!empty($user->email)){
          // Send Email
          $content = str_replace("{username}", $user->username, $_POST["message"]);
          $content = str_replace("{email}", $user->email, $content);
          $content = str_replace("{date}", date("F-m-d H:i", strtotime($user->date)), $content);

          $mail["to"] = $user->email;
          $mail["subject"] = $_POST["subject"];              
          $mail["message"] = "<td class='column' style='padding: 0;vertical-align: top;text-align: left'>
                               <div>
                                  <div class='column-top' style='font-size: 50px;line-height: 50px'>&nbsp;</div>
                               </div>
                               <table class='contents' style='border-collapse: collapse;border-spacing: 0;width: 100%'>
                                  <tbody>
                                     <tr>
                                        <td class='padded' style='padding: 0;vertical-align: top;padding-left: 50px;padding-right: 50px'>
                                          $content
                                        </td>
                                     </tr>
                                  </tbody>
                               </table>
                               <div class='column-bottom' style='font-size: 26px;line-height: 26px'>&nbsp;</div>
                            </td>";

          Main::send($mail);   
        }
      }
      return Main::redirect(Main::ahref("tools/newsletter","",FALSE),array("success","邮件发送成功."));
    }
  }   
  /**
   * [tools description]
   * @author 某某 <https://www.domain.com>
   * @version 5.5
   * @return  [type] [description]
   */
  protected function emails(){

    if(isset($_POST["token"])){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Validate Token
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("emails","",FALSE),array("danger","Invalid token, please try again."));
      }   

      foreach ($_POST as $key => $value) {
        $key = str_replace("_",".", $key);

        if(in_array($key, ["email.registration", "email.activation", "email.activated", "email.reset"])){
          $this->db->update("settings", ["var" => "?"], ["config" => "?"], [$value, $key]);
        }
      }

      return Main::redirect(Main::ahref("emails","",FALSE),array("success","Email message has been updated."));

    }    


    Main::cdn("summernote", NULL, TRUE);
    Main::admin_add("<script>$(document).ready(function() { $('.editor').summernote({height: 200,  toolbar: [
      ['style', ['bold', 'italic', 'underline', 'clear']],
      ['font', ['strikethrough', 'superscript', 'subscript']],
      ['fontsize', ['fontsize']],
      ['color', ['color','link']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['height', ['height']]
    ]}) });</script>","custom", FALSE);    

    Main::set("title","Emails");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }    
  /**
   * Get Theme Styles
   * @since 4.1
   **/
  protected function style(){
    if(!is_dir(TEMPLATE."/styles/")) return FALSE;
    $html = '<div class="form-group">
          <label class="col-sm-3 control-label">Style</label>
          <div class="col-sm-9">
            <ul class="themes-style">
            <li class="dark"><a href="#" data-class="" '.($this->config["style"]==""?"class='current'":'').'>Dark</a></li>';        
    foreach (new RecursiveDirectoryIterator(TEMPLATE."/styles/") as $path){
      if(!$path->isDir() && Main::extension($path->getFilename())==".css"){  
        $name=str_replace(".css", "", $path->getFilename());
        $html.='<li class="'.$name.'"><a href="#" data-class="'.$name.'" '.($this->config["style"]==$name?"class='current'":'').'>'.ucfirst($name).'</a></li>';                  
      }
    }             
    $html.='</ul> 
          <input type="hidden" name="style" value="'.$this->config["style"].'" id="theme_value"> 
          <p class="help-block">The default theme supports these styles.</p>
        </div>
      </div>';
    return $html;
  }      
  /**
   * Themes
   * @since 5.4
   **/
  protected function themes(){
    /**
     * Activate Plug
     */
    if(isset($_GET["activated"])){
      Main::plug("admin.theme.activate");      
    }

    if($this->do == "editor") return $this->editor();
    if($this->do == "options") return $this->options();

    // Activate Theme
    if($this->do == "activate" && !empty($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("theme-{$this->id}")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger",e("Security token expired, please try again.")));
      }       

      if(!file_exists(ROOT."/themes/{$this->id}/style.css")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger","Sorry this theme cannot be activated because it is missing the stylesheet.")); 
      }

      if($this->id != $this->config["theme"]){
        Main::plug("admin.theme.deactivate"); 
      }

      if($this->db->update("settings",array("var"=>"?"),array("config" => "?"),array(Main::clean($this->id,3,TRUE),"theme"))){
        Main::redirect(Main::ahref("themes?activated=true","",FALSE),array("success","Theme has been activated."));
      }      
      return Main::redirect(Main::ahref("themes","",FALSE),array("danger","An unexpected issue occurred, please try again."));
    }
    // Clone Theme
    if($this->do=="copy" && !empty($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("copy-{$this->id}")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger",e("Security token expired, please try again.")));
      }       
      $this->copy_folder(ROOT."/themes/{$this->id}",ROOT."/themes/{$this->id}".rand(0,9));
      return Main::redirect(Main::ahref("themes","",FALSE),array("success","Theme has been successfully cloned."));
    }    
 
    // Get Themes
    $theme_list="";
    foreach (new RecursiveDirectoryIterator(ROOT."/themes/") as $path){
      if($path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && file_exists(ROOT."/themes/".$path->getFilename()."/style.css")){          

        $data=token_get_all(file_get_contents(ROOT."/themes/".$path->getFilename()."/style.css"));
        $data=isset($data[0][1])?$data[0][1]:FALSE;
        if($data){
          if(preg_match("~Theme Name:\s(.*)~", $data,$name)){
            $name=strip_tags(trim($name[1]));
          }        
          if(preg_match("~Author:\s(.*)~", $data,$author)){
            $author=strip_tags(trim($author[1]));
          }        
          if(preg_match("~Author URI:\s(.*)~", $data,$url)){
            $url=strip_tags(trim($url[1]));
          }
          if(preg_match("~Version:\s(.*)~", $data,$version)){
            $version=strip_tags(trim($version[1]));
          }
          if(preg_match("~Date:\s(.*)~", $data,$date)){
            $date=strip_tags(trim($date[1]));
          }
        }
        $name=isset($name) && !is_array($name)? $name : "No Name";
        $author=isset($author) && !is_array($author)? $author : "Unknown";
        $url=isset($url) && !is_array($url)? $url : "#none";
        $version=isset($version) && !is_array($version)? $version : "1.0";
        $date=isset($date) && !is_array($date)? $date : "";

        if(file_exists(ROOT."/themes/".$path->getFilename()."/screenshot.png")){
          $screenshot=$this->config["url"]."/themes/".$path->getFilename()."/screenshot.png";
        }else{
          $screenshot=$this->config["url"]."/static/noscreen.png";
        }
        $theme_list.="<div class='theme-list'>";
          $theme_list.="<div class='theme-img'><img src='$screenshot' alt='$name'></div>";
          $theme_list.="<div class='theme-info'>";
          $theme_list.="<strong>$name</strong>";
          if($this->config["theme"]!==$path->getFilename()) {
            $theme_list.="<div class='btn-group btn-group-xs pull-right'><a href='".Main::ahref("themes/activate/{$path->getFilename()}").Main::nonce('theme-'.$path->getFilename())."' class='btn btn-success'>默认</a><a href='".Main::ahref("themes/copy/{$path->getFilename()}").Main::nonce('copy-'.$path->getFilename())."' class='btn btn-info delete'>克隆</a></div>";
          }else{
            $theme_list.="<div class='btn-group btn-group-xs pull-right'><a class='btn btn-dark'>默认</a><a href='".Main::ahref("themes/copy/{$path->getFilename()}").Main::nonce('copy-'.$path->getFilename())."' class='btn btn-info delete'>克隆</a></div>";
          }
        $theme_list.="<p>By <a href='$url' rel='nofollow' target='_blank'>$author</a> (v$version)</p></div></div>";
      }
    }

    Main::set("title","主题设置");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
  /**
   * [hasOption description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  boolean [description]
   */
  protected function hasOptions(){
    if($option = Main::plug("admin.theme.hasOption")){
      return TRUE;
    }
    return FALSE;
  }
  /**
   * [options description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function options(){  
    $get = Main::plug("admin.theme.getOptions");

    Main::set("title","主题设置");
    $this->header();
    echo $get;
    $this->footer();    
  }
  /**
   * [editor description]
   * @author 某某 <https://www.domain.com>
   * @version 5.4
   * @return  [type] [description]
   */
  protected function editor(){
    // Update Theme
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("themes/editor","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if($_POST["theme_files"]=="style"){
        $file_path=TEMPLATE."/style.css";
      }else{
        $file_path=TEMPLATE."/".Main::clean($_POST["theme_files"],3,TRUE).".php";
      }
      if(file_exists($file_path)){
        $file = fopen($file_path, 'w') or die( Main::redirect(Main::ahref("themes","",FALSE),array("danger","Cannot open file. Please make sure that the file is writable.")));
        fwrite($file, $_POST["content"]);
        fclose($file);
        return Main::redirect(Main::ahref("themes/editor/".Main::clean($_POST["theme_files"],3,TRUE),"",FALSE),array("success","File has been successfully edited."));
      }
    } 

    // Get Files
    $themeFiles = $this->themeFiles();
    // Get Current File
    $currentFile = $this->currentFile();
    // Add ACE from CDN
    Main::cdn("ace","",1);
    Main::admin_add('
      <script type="text/javascript">
        var editor = ace.edit("code-editor");
            editor.setTheme("ace/theme/xcode");
            editor.getSession().setMode("ace/mode/'.$currentFile["type"].'");
        $(document).ready(function(){
          $("#form-editor").submit(function(){
            $("#code").val(editor.getSession().getValue());
          });
        });
      </script>',"custom",1);

    Main::set("title","编辑主题");
    $this->header();

    echo "<div class='editor'>
            <form action='".Main::ahref("themes/editor")."' method='post' class='form' id='form-editor'>
              <textarea name='content' id='code' class='form-control hidden' rows='1'></textarea>
              <div class='header'>
                <div class='row'>
                  <div class='col-sm-6'>
                    编辑文件: {$currentFile["current"]}
                  </div>
                  <div class='col-sm-6'>
                    <select name='theme_files' id='theme_files' style='max-width: 250px' class='pull-right'>
                      {$themeFiles}
                    </select>
                  </div>
                </div>
              </div>
              <div id='code-editor'>{$currentFile["content"]}</div>
              <br class='clear'>
              ".Main::csrf_token(TRUE)."
              <button class='btn btn-primary btn-lg'>编辑</button>
            </form>  
          </div>"; 
    $this->footer();   
  }
      /**
       * Theme Files
       * @since 4.0
       **/
      protected function themeFiles(){
        $data="";
        foreach (new RecursiveDirectoryIterator(ROOT."/themes/{$this->config["theme"]}/") as $path){
          if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && (Main::extension($path->getFilename())==".php" || Main::extension($path->getFilename())==".css")){
            $file=explode(".",$path->getFilename());
            $file=$file[0];
            $name=ucwords(str_replace("_", " ", $file));
            $code=strtolower($file);      
            if($path->getFilename()=="style.css") {
              $name="样式";
              $data.="<option value='$code' ".(empty($this->id) || $this->id=="style" ? "selected":"").">$name ({$path->getFilename()})</option>";
            }elseif($path->getFilename()=="index.php"){
              $name="首页";              
              $data.="<option value='$code' ".($this->id==$code ? "selected":"").">$name ({$path->getFilename()})</option>";              
            }else{
              $data.="<option value='$code' ".($this->id==$code ? "selected":"").">$name ({$path->getFilename()})</option>";
            }
          }
        }
        return $data;
      }
      /**
       * Current Theme
       * @since 4.0
       **/
      protected function currentFile(){
        $data=array();
        // Get File
        if(!empty($this->id) && $this->id != "style"){
          if(!empty($this->id) && file_exists(ROOT."/themes/{$this->config["theme"]}/{$this->id}.php")){
            $data["type"]="html";
            $data["current"] = ucfirst($this->id)." File";
             // Disable if demo
            if($this->config["demo"]){
              $data["content"]="Content is hidden in demo";
            }else{
              $data["content"]=htmlentities(file_get_contents(ROOT."/themes/{$this->config["theme"]}/{$this->id}.php", "r"));
            }            
          }else{
            return Main::redirect(Main::ahref("themes/editor","","FALSE"),array("danger","Theme file doesn't exist."));
          }
        }else{
          $data["type"]="css";
          $data["current"]="Main Stylesheet (style.css)";
          if($this->config["demo"]){
            $data["content"]="Content is hidden in demo";
          }else{          
            $data["content"]=htmlentities(file_get_contents(ROOT."/themes/{$this->config["theme"]}/style.css", "r"));
          }
        }
        return $data;
      }   

  /**
   * Languages
   * @since 5.3
   **/
  protected function languages(){
    if(in_array($this->do, array("add","edit","delete"))){
      $fn = "languages_{$this->do}";
      return $this->$fn();
    }    
    // Update Language
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if(empty($_POST["language_name"])) return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Language name cannot be empty!"));
      // Update Language
      $file = substr(strtolower(Main::clean(trim($_POST["language_name"]),3,TRUE)), 0, 2).".php";
      $handle = fopen(ROOT."/includes/languages/".$file, 'w') or Main::redirect(Main::ahref("languages","",FALSE),array("danger","Cannot create file. Make sure that the folder is writable."));

      $comment="<?php\n";
      $comment.="/*\n* Language: ".ucfirst(Main::clean($_POST["language_name"],3,TRUE))."\n* Author: You\n* Author URI: {$this->config["url"]}\n* Translator: 某某短网址系统\n* Date: ".date("Y-m-d H:i:s",time())."\n* ---------------------------------------------------------------\n* Important Notice: Make sure to only change the right-hand side\n* DO NOT CHANGE THE LEFT-HAND SIDE\n* Edit the text between double-quotes \"DONT EDIT\"=> \"\" on the right side\n* Make sure to not forget any quotes \" and the comma , at the end\n* ---------------------------------------------------------------\n*/\n";
      $comment.='$lang=array(';

      fwrite($handle, $comment);
      foreach ($_POST["text"] as $o => $t) {
        fwrite($handle, "\n\"".strip_tags($o,"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($t,"<b><i><s><u><strong>")."\",");
      }
      fwrite($handle, "); ?>");
      fclose($handle);    
      return Main::redirect(Main::ahref("languages","",FALSE),array("success","语言文件创建成功."));      
    }  

    $languages = [];
    foreach (new RecursiveDirectoryIterator(ROOT."/includes/languages/") as $path){
      if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && $path->getFilename()!=="index.php" && $path->getFilename()!=="lang_sample.php" && Main::extension($path->getFilename())==".php"){  

        $file=explode(".", $path->getFilename());
        $file=$file[0];
        $code=strtolower($file);
        $data=token_get_all(file_get_contents($path));
        $data=isset($data[1][1])?$data[1][1]:FALSE;
          if($data){
            if(preg_match("~Language:\s(.*)~", $data,$name)){
              $name = Main::truncate(strip_tags(trim($name[1])),10);
            }
            if(preg_match("~Author:\s(.*)~", $data,$author)){
              $author = strip_tags(trim($author[1]));
            }           
            if(preg_match("~Date:\s(.*)~", $data,$date)){
              $date = strip_tags(trim($date[1]));
            }                                      
          }else{
            $name="Unknown";
            $author="Unknown";
            $date="Unknown";
          }
          include(ROOT."/includes/languages/{$code}.php");
          $total = count($lang);
          $filled = count(array_filter($lang));
          $percent = round(($filled / $total)*100, 1);
          $languages[] = [
              "name" => $name,
              "code" => $code,
              "author" => $author,
              "date" => $date,
              "percent" => $percent
          ];
      }
    }

    Main::set("title","语言翻译");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }    
  /**
   * [languages_add description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function languages_add(){

    // Update Language
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if(empty($_POST["language_name"])) return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Language name cannot be empty!"));
      // Update Language
      $file = substr(strtolower(Main::clean(trim($_POST["language_name"]),3,TRUE)), 0, 2).".php";
      $handle = fopen(ROOT."/includes/languages/".$file, 'w') or Main::redirect(Main::ahref("languages","",FALSE),array("danger","Cannot create file. Make sure that the folder is writable."));

      $comment="<?php\n";
      $comment.="/*\n* Language: ".ucfirst(Main::clean($_POST["language_name"],3,TRUE))."\n* Author: You\n* Author URI: {$this->config["url"]}\n* Translator: 某某短网址系统\n* Date: ".date("Y-m-d H:i:s",time())."\n* ---------------------------------------------------------------\n* Important Notice: Make sure to only change the right-hand side\n* DO NOT CHANGE THE LEFT-HAND SIDE\n* Edit the text between double-quotes \"DONT EDIT\"=> \"\" on the right side\n* Make sure to not forget any quotes \" and the comma , at the end\n* ---------------------------------------------------------------\n*/\n";
      $comment.='$lang = array(';

      fwrite($handle, $comment);
      foreach ($_POST["text"] as $o => $t) {
        fwrite($handle, "\n\"".strip_tags($o,"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($t,"<b><i><s><u><strong>")."\",");
      }
      if(isset($_POST["new"]["original"]) && !empty($_POST["new"]["original"])){
        fwrite($handle, "\n\"".strip_tags($_POST["new"]["original"],"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($_POST["new"]["translated"],"<b><i><s><u><strong>")."\",");
      }
      fwrite($handle, "); ?>");
      fclose($handle);    
      return Main::redirect(Main::ahref("languages/add","",FALSE),array("success","Language file has been successfully."));      
    }  

    // Add language from Sample
    $content = "";
    if(!file_exists(ROOT."/includes/languages/lang_sample.php")){
      $content="<p class='alert alert-danger'>Sample file (lang_sample.php) is not available. Please upload that in the includes/languages/ folder. This editor will not work until that file is properly uploaded there and is accessible!</p>";
    }else{
      // Get File
      include(ROOT."/includes/languages/lang_sample.php");
      // Check if properly formated
      if(!isset($lang) || !is_array($lang)){
        return "<p class='alert alert-danger'>The sample translation file appears to be empty or corrupted. Please verify that it is properly formated!</p>";
      }          
      // Generate Form
      $content .= "<form action='".Main::ahref("languages")."' method='post' class='form'>";
      $content .= '<p class="alert alert-warning">
               要创建新的语言文件，请在下面的字段中编写语言，并翻译其下方的textarea中的每个字符串。文本将立即显示，所以请记住尊重字母案例。请记住，语言代码将是该语言的前两个字母：例如，如果语言名称是法语，则语言代码将为fr。如果由于某种原因，此编辑器不适合您，您可以按照文档手动翻译它。强烈建议您经常保存以防止数据丢失。如果你不翻译所有内容都没关系，只要确保定期保存!
              </p>';
      $content.="<div class='form-group'>
          <label for='language_name' class='control-label'>新语言名称 (例如. French)</label>
          <input type='text' class='form-control' name='language_name' id='language_name' value=''>                
        </div><h4 class='page-header'>翻译字符串</h4><div class='row'>";        
      $i = 0;
      foreach ($lang as $original => $translation){       
        $content.= "<div class='col-md-4'><div class='form-group'>
          <label class='control-label'>$original</label>
          <textarea name='text[$original]' class='form-control' style='min-height:60px;'>$translation</textarea>
        </div></div>";
        $i++;
        if($i%3 == "0") $content.= "</div><hr><div class='row'>";
      }      
      $content .= "</div>";
      $content .= "<h4 class='page-header'>新字符串</h4><p>如果您在整个脚本中添加了自定义字符串，则可以在此处添加原始文本和翻译文本。如果您有多个字符串，请更新此文件，然后添加新文件.</p>";
      $content .= "<div class='row'>
                    <div class='col-md-6'>
                      <textarea name='new[original]' class='form-control' style='min-height:60px;'></textarea>
                    </div>
                    <div class='col-md-6'>
                      <textarea name='new[translated]' class='form-control' style='min-height:60px;'></textarea>
                    </div>
                   </div><hr>";      
      $content .=Main::csrf_token(TRUE);
      $content .="<button class='btn btn-primary'>创建语言文件</button></form>";   
      $header = "添加语言";
      Main::set("title","添加语言");
      $this->header();
      include($this->t("edit"));
      $this->footer();           
    }
  } 
  /**
   * [languages_delete description]
   * @author 某某 <https://www.domain.com>
   * @version 5.3
   * @return  [type] [description]
   */
  protected function languages_delete(){
    // Delete Language
    if(!empty($this->id) && strlen($this->id)=="2" && file_exists(ROOT."/includes/languages/{$this->id}.php")){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("delete_language-{$this->id}")){
        return Main::redirect(Main::ahref("languages","",FALSE),array("danger",e("Security token expired, please try again.")));
      }
      unlink(ROOT."/includes/languages/{$this->id}.php");
      return Main::redirect(Main::ahref("languages","",FALSE),array("success","语言文件删除成功."));
    }
  }
  /**
   * Get Language
   * @since 5.3
   **/
  protected function languages_edit(){
    // Check if it needs to edited
    if(empty($this->id)) return Main::redirect(Main::ahref("languages","",FALSE),array("danger",e("未找到翻译文件.")));

    if(strlen($this->id)!="2" || !file_exists(ROOT."/includes/languages/{$this->id}.php")){
      return Main::redirect(Main::ahref("languages","",FALSE),array("danger",e("文件不可写!")));
    }

    // Update Language
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if(empty($_POST["language_name"])) return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Language name cannot be empty!"));
      // Update Language
      $file = substr(strtolower(Main::clean(trim($_POST["language_name"]),3,TRUE)), 0, 2).".php";
      $handle = fopen(ROOT."/includes/languages/".$file, 'w') or Main::redirect(Main::ahref("languages","",FALSE),array("danger","Cannot create file. Make sure that the folder is writable."));

      $comment="<?php\n";
      $comment.="/*\n* Language: ".ucfirst(Main::clean($_POST["language_name"],3,TRUE))."\n* Author: You\n* Author URI: {$this->config["url"]}\n* Translator: 某某短网址系统\n* Date: ".date("Y-m-d H:i:s",time())."\n* ---------------------------------------------------------------\n* Important Notice: Make sure to only change the right-hand side\n* DO NOT CHANGE THE LEFT-HAND SIDE\n* Edit the text between double-quotes \"DONT EDIT\"=> \"\" on the right side\n* Make sure to not forget any quotes \" and the comma , at the end\n* ---------------------------------------------------------------\n*/\n";
      $comment.='$lang = array(';

      fwrite($handle, $comment);
      foreach ($_POST["text"] as $o => $t) {
        fwrite($handle, "\n\"".strip_tags($o,"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($t,"<b><i><s><u><strong>")."\",");
      }
      if(isset($_POST["new"]["original"]) && !empty($_POST["new"]["original"])){
        fwrite($handle, "\n\"".strip_tags($_POST["new"]["original"],"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($_POST["new"]["translated"],"<b><i><s><u><strong>")."\",");
      }
      fwrite($handle, "); ?>");
      fclose($handle);    
      return Main::redirect(Main::ahref("languages/edit/{$this->id}","",FALSE),array("success","Language file has been successfully."));      
    }  

    $data = token_get_all(file_get_contents(ROOT."/includes/languages/{$this->id}.php"));
    $data = isset($data[1][1])?$data[1][1]:FALSE;
    if($data){
      if(preg_match("~Language:\s(.*)~", $data,$name)){
        $name = strip_tags(trim($name[1]));
      }
    }
    // Get File
    include(ROOT."/includes/languages/{$this->id}.php");          
    // Check if properly formated
    if(!isset($lang) || !is_array($lang)){
      return "<p class='alert alert-danger'>The translation file appears to be empty or corrupted. Please verify that it is properly formated!</p>";
    }
    // Generate form
    $content = "";
    $content .= "<form action='".Main::ahref("languages/edit/{$this->id}")."' method='post' class='form'>";
    $content .= '<p class="alert alert-warning">
            对于下面的每个字符串，将标签的翻译文本写入文本区域。允许HTML标记：<b><i><s><u><strong>。强烈建议您经常保存以防止数据丢失。如果你不翻译所有的东西，那就没关系了，只要定期保存就行了!
            </p>';
    $content.= "<div class='form-group'>
        <label for='language_name' class='control-label'>编辑语言名称 (例如. French)</label>
        <input type='text' class='form-control' name='language_name' id='language_name' value='$name'>                
      </div><h4 class='page-header'>翻译字符串</h4><div class='row'>";  
    $i = 0;      
    foreach ($lang as $original => $translation){
      $content.= "<div class='col-md-4'><div class='form-group'>
        <label class='control-label'>$original</label>
        <textarea name='text[$original]' class='form-control' style='min-height:60px;'>$translation</textarea>
      </div></div>";
      $i++;
      if($i%3 == "0") $content.= "</div><hr><div class='row'>";
    }      
    $content .= "</div>";
    $content .= "<h4 class='page-header'>新字符串</h4><p>如果您在整个脚本中添加了自定义字符串，则可以在此处添加原始文本和翻译文本。如果您有多个字符串，请更新此文件，然后添加新文件.</p>";
    $content .= "<div class='row'>
                  <div class='col-md-6'>
                    <textarea name='new[original]' class='form-control' style='min-height:60px;'></textarea>
                  </div>
                  <div class='col-md-6'>
                    <textarea name='new[translated]' class='form-control' style='min-height:60px;'></textarea>
                  </div>
                 </div><hr>";
    $content .= Main::csrf_token(TRUE);          
    $content .= "<button class='btn btn-primary'>更新翻译</button> &nbsp;";
    $content .= "<a href='".Main::ahref("languages/delete/{$this->id}").Main::nonce("delete_language-{$this->id}")."' class='btn btn-danger delete'>删除</a></form>";           

    $header = "编辑语言翻译: {$name}";
    Main::set("title","编辑语言: {$name}");
    $this->header();
    include($this->t("edit"));
    $this->footer();
  }  

  /**
   * Update Notification  
   * @since 5.3.2 
   */
  public function update_notification($version = FALSE){
    if($this->config["update_notification"]){
      $c = Main::curl("https://gempixel.com/update.php?p=".md5('shortener'));
      $c = json_decode($c,TRUE);
      if(isset($c["status"]) && $c["status"]=="ok"){
        if(_VERSION < $c["current_version"]){
          if($version == TRUE){
            return [$c["current_version"], "<div class='alert alert-success'>This script has been updated to version {$c["current_version"]}. You can run the <a href='".Main::ahref("update")."' class='button green' style='color:#fff'><u>automatic updater</u></a> or you can download it from <a href='http://codecanyon.net/downloads' target='_blank' class='button green' style='color:#fff'><u>CodeCanyon</u></a> and manually update it.</div>"];
          }else{
            return "<div class='alert alert-success'>This script has been updated to version {$c["current_version"]}. You can run the <a href='".Main::ahref("update")."' class='button green' style='color:#fff'><u>automatic updater</u></a> or you can download it from <a href='http://codecanyon.net/downloads' target='_blank' class='button green' style='color:#fff'><u>CodeCanyon</u></a> and manually update it.</div>";
          }
        }
      }
    }
    return FALSE;
  }     
  /**
   * [update description]
   * @author 某某 <https://www.domain.com>
   * @version 5.4
   * @return  [type] [description]
   */
  protected function update(){

    if(isset($_POST["token"])){

      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("update","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      
      $this->config["purchasecode"] = Main::clean($_POST["code"], 3, TRUE);

      include(ROOT."/includes/AutoUpdate.class.php");
      $update = new fengge\AutoUpdate($this->config["purchasecode"]);

      try {

        if($update->install()){
          
          $this->db->update("settings", ["var" => Main::clean($_POST["code"], 3, TRUE)], ["config" => "purchasecode"]);
          return Main::redirect(Main::ahref("update","",FALSE),array("success", "Script has been successfully updated"));
        }

      }catch(Exception $e){

        return Main::redirect(Main::ahref("update","",FALSE),array("danger",$e->getMessage()));
      }
    }
    $Notification = $this->update_notification($version = TRUE);

    $beforehead = $Notification[1];

    if($beforehead) {
      $content = "<h4 class='page-header'>New update available <small>(version ".$Notification[0].")</small></h4><p>You can run automatic updater to update this script. To be safe, we recommend you backup your site regularly. You will need your purchase code to update automatically. You can find your purhcase key in the downloads section of codecayon. Also please note that this updater will replace all files. This means all of your custom changes will be overwritten.</p>";
      $content .= "<ul class='checks'>";
        if(!in_array('curl', get_loaded_extensions())){ 
          $content .= "<li class='failed'><i class='glyphicon glyphicon-remove'></i>cURL library is not available. Please update manually.</li>";
        } else{
          $content .= "<li class='passed'><i class='glyphicon glyphicon-ok'></i>cURL library is available.</li>";
        } 
        if(!class_exists("ZipArchive")){
          $content .= "<li class='failed'><i class='glyphicon glyphicon-remove'></i>ZipArchive library is not available. Please update manually.</li>";
        } else{
          $content .= "<li class='passed'><i class='glyphicon glyphicon-ok'></i>ZipArchive library is available.</li>";
        } 
        if(!is_writable(ROOT)){
          $content .= "<li class='failed'><i class='glyphicon glyphicon-remove'></i>".ROOT." is not writable. Please change the permission to 755.</li>";
        } else{
          $content .= "<li class='passed'><i class='glyphicon glyphicon-ok'></i>".ROOT." is writable.</li>";
        }    
            
      $content .= "</ul>";
      $content .= "<form action='".Main::ahref("update")."' method='post'>";
        $content .= "<div class='row'>
                      <div class='col-md-4'>
                        <label for='code'>Purchase Code</label>
                        <input type='text' class='form-control' name='code' id='code' value='{$this->config["purchasecode"]}'>
                      </div>
                     </div><hr>";
        $content .= Main::csrf_token(TRUE);          
        $content .= "<button type='submit' class='btn btn-primary'>Update Script</button> &nbsp;";
      $content .= "</form>";      
    }else{
    $content = "<h4 class='page-header'>No update available</h4><p>You can run automatic updater to update this script. To be safe, we recommend you backup your site regularly. You will need your purchase code to update automatically. You can find your purchase key in the downloads section of codecayon.</p>";
    }     

    $header = "AutoUpdate Script - current version "._VERSION."";

    Main::set("title", "AutoUpdate Script");
    $this->header();
    include($this->t("edit"));
    $this->footer();    
  }

  ################################################################################################      
  ###   Admin helper methods: Please don't edit these methods as it might cause instability!   ###
  ################################################################################################

  /**
   * Header
   * @since 4.0 
   **/
  protected function header(){
    include($this->t(__FUNCTION__));
  }
  /**
   * Footer
   * @since 4.0 
   **/
  protected function footer(){
    include($this->t(__FUNCTION__));
  }  
  /**
   * Template File
   * @since 4.0
   **/
  protected function t($file){
    if(file_exists(ROOT."/admin/system/$file.php")){
      return ROOT."/admin/system/$file.php";
    }else{
      return ROOT."/admin/system/index.php";
    }
  }
  /**
   * [installExtended description]
   * @author 某某 <https://www.domain.com>
   * @version 1.0
   * @param   [type] $R [description]
   * @return  [type]    [description]
   */
  private function installExtended($R){
    $db = str_replace("_PRE_", $this->config["prefix"], $R);
    $queries = explode("|", $db);
    foreach ($queries as $query) {
      if(!$this->db->run($query)){
        return FALSE;
      }
    }
    return Main::redirect(Main::ahref("subscription","",FALSE),array("success","Subscription has been enabled. You may now use Stripe."));  
  }
  /**
   * Copy Folder
   * @since 4.0
   **/  
  protected function copy_folder($src,$dst) { 
    // Disable this for demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));    
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
      if (( $file != '.' ) && ( $file != '..' )) { 
        if ( is_dir($src . '/' . $file) ) { 
         $this->copy_folder($src . '/' . $file,$dst . '/' . $file); 
        } 
        else { 
          copy($src . '/' . $file,$dst . '/' . $file); 
        } 
      } 
    } 
    closedir($dir); 
  }   
}