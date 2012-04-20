<?php
/*
 * Copyright 2011-2012 Gerwin Sturm
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
  include "config.php";

  $user_ip = $_SERVER["REMOTE_ADDR"];
  if (!$user_ip || $user_ip == "") {
    $user_ip = $_SERVER["SERVER_ADDR"];
  }
  require_once $gapi_client_path . "apiClient.php";
  require_once $gapi_client_path . "contrib/apiPlusService.php";
  session_start();
  $client = new apiClient();
  $client->setApplicationName("All my +");
  $client->setClientId($client_id);
  $client->setClientSecret($client_secret);
  $client->setRedirectUri($base_url . "index.php");
  $client->setDeveloperKey($developer_key);
  $client->setScopes(array("https://www.googleapis.com/auth/plus.me"));
  $plus = new apiPlusService($client);
  $maxresults = 100;

  if (isset($_REQUEST["logout"])) {
    unset($_SESSION["access_token"]);
    header("Location: " . $base_url);
  }

  if (isset($_GET["code"])) {
    $client->authenticate();
    $_SESSION["access_token"] = $client->getAccessToken();
    try {
      $me = $plus->people->get("me");
      header("Location: " . $base_url . "u/" . $me["id"]);
    } catch (Exception $e) {
      unset($_SESSION["access_token"]);
      header("Location: " . $base_url . "?quota_exceeded");
    }
  }

  if (isset($_GET["error"])) {
    unset($_SESSION["access_token"]);
    header("Location: " . $base_url);
  }

  if ($_POST["userid"] && $_POST["userid"] != "") {
    $pattern = "/[0-9]{10,30}/";
    if (preg_match($pattern, $_POST["userid"], $matches)) {
      header("Location: " . $base_url . "u/" . $matches[0]);
    } else {
      header("Location: " . $base_url);
    }
  }

  $request = $_SERVER["REQUEST_URI"];
  $p = strrpos($request, "/u/");
  if (!($p === false)) {
    $request = substr($request, $p);
  }
  if ($request == "?quota_exceeded") {
    $q_user = "";
  } else {
    $p = strrpos($request, "?");
    if (!($p === false)) {
      $q_user = substr($request, 3, $p-3);
    } else {
      $q_user = substr($request, 3);
    }
  }

  if (isset($_SESSION["access_token"])) {
    $client->setAccessToken($_SESSION["access_token"]);
  }

  if ($client->getAccessToken()) {
    try {
      $me = $plus->people->get("me");
      $_SESSION["access_token"] = $client->getAccessToken();
      if ($q_user == "") {
        header("Location: " . $base_url . "u/" . $me["id"]);
      }
      $login_id = $me["id"];
      $login_name = $me["displayName"];
    } catch (Exception $e) {
      unset($_SESSION["access_token"]);
      $authUrl = $client->createAuthUrl();
      $authUrl = str_replace("&amp;", "&", $authUrl);
      $authUrl = str_replace("&", "&amp;", $authUrl);
    }
  } else {
    $authUrl = $client->createAuthUrl();
    $authUrl = str_replace("&amp;", "&", $authUrl);
    $authUrl = str_replace("&", "&amp;", $authUrl);
  }

  $num_activities = 0;
  $activities = array();
  $str_author_id = "";
  $str_author_name = "";
  $str_author_url = "";
  $str_author_pic = "";
  $str_errors = "";

  if ($q_user != "") {
    $chk_more = true;
    $optParams = array("userIp" => $user_ip);
    try {
      $actor = $plus->people->get($q_user);
      if (isset($actor["id"])) {
        $str_author_id = $actor["id"];
        $str_author_name = $actor["displayName"];
        $str_author_url = $actor["url"];
        if (isset($actor["image"])){
          if (isset($actor["image"]["url"])){
            $str_author_pic = $actor["image"]["url"];
            $str_author_pic = str_replace("?sz=50", "?sz=200", $str_author_pic);
          }
        }
      }
    } catch (Exception $e) {
      $str_errors = $str_errors.$e->getMessage() . "<br>";
    }
  }
?>
<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/Person" style="overflow-y: scroll;">
<head>
  <meta charset="UTF-8">
<?php
  if ($str_author_name=="") {
    printf("  <title>All my +</title>\n");
    printf("  <meta itemprop=\"name\" content=\"All my +\">\n");
    printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of your public g+ activities.\">\n");
  } else {
    printf("  <title>All my + are belong to %s</title>\n",$str_author_name);
    printf("  <meta itemprop=\"name\" content=\"All my + data for %s\">\n",$str_author_name);
    printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of the g+ activities of %s.\">\n",$str_author_name);
    printf("  <meta itemprop=\"image\" content=\"%s\">\n",$str_author_pic);
  }
?>
  <link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>style.css">
  <link rel="shortcut icon" href="<?php echo $base_url; ?>favicon.ico">
  <link rel="icon" href="<?php echo $base_url; ?>favicon.ico">
  <link href="https://plus.google.com/105696887942257432718" rel="publisher">
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script type="text/javascript" src="https://www.google.com/jsapi?key=<?php echo $jsapi_key; ?>"></script>
  <script type="text/javascript" src="<?php echo $base_url; ?>sorttable.js"></script>
  <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
  <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-16652516-7']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
  </script>
  <script type="text/javascript">

    var
      activities = [],
      S_VARS = 12,
      S_POSTS = 0,
      S_LOC = 1,
      S_PHOTOS = 2,
      S_GIFS = 3,
      S_VIDEOS = 4,
      S_LINKS = 5,
      S_COMMENTS = 6,
      S_CPP = 7,
      S_PLUSONES = 8,
      S_PPP = 9,
      S_RESHARES = 10,
      S_RPP = 11,
      ST_TOTAL = 0,
      ST_ORIGINAL = 1,
      ST_RESHARED = 2,
      P_ID = 0,
      P_NAME = 1,
      P_URL = 2,
      P_PIC = 3,
      P_RESHARED = 4,
      P_RESHARES = 5,
      P_COMMENTS = 6,
      P_PLUSONES = 7,
      total_stats, hour_stats, day_stats, daily_stats, min_date, max_date,
      max_comments, max_comments_post, max_reshares, max_reshares_post, max_plusones, max_plusones_post,
      day_data, day_view, day_chart, weekday_data, weekday_view, weekday_chart, hour_data, hour_view, hour_chart,
      people, sort_function;

    Date.prototype.yyyymmddhhmmss = function () {
      "use strict";
      var y, m, d, h, min, sec;
      y = this.getFullYear().toString();
      m = (this.getMonth() + 1).toString();
      d  = this.getDate().toString();
      h = this.getHours().toString();
      min = this.getMinutes().toString();
      sec = this.getSeconds().toString();
      return y + (m[1] ? m : "0" + m[0]) + (d[1] ? d : "0" + d[0]) + (h[1] ? h : "0" + h[0]) + (min[1] ? min : "0" + min[0]) + (sec[1] ? sec : "0" + sec[0]);
    };

    Date.prototype.nice_date = function () {
      "use strict";
      var y, m, d, h, min, sec;
      y = this.getFullYear().toString();
      m = (this.getMonth() + 1).toString();
      d  = this.getDate().toString();
      h = this.getHours().toString();
      min = this.getMinutes().toString();
      sec = this.getSeconds().toString();
      return y + "-" + (m[1] ? m : "0" + m[0]) + "-" + (d[1] ? d : "0" + d[0]) + " " + (h[1] ? h : "0" + h[0]) + ":" + (min[1] ? min : "0" + min[0]) + ":" + (sec[1] ? sec : "0" + sec[0]);
    };

    Date.prototype.nice_short_date = function () {
      "use strict";
      var y, m, d;
      y = this.getFullYear().toString();
      m = (this.getMonth() + 1).toString();
      d  = this.getDate().toString();
      return y + "-" + (m[1] ? m : "0" + m[0]) + "-" + (d[1] ? d : "0" + d[0]);
    };

    function menu_click(name) {
      "use strict";
      $(".menue").removeClass("menue_sel");
      $(".menue").addClass("menue_unsel");
      $("#men_"+name).removeClass("menue_unsel");
      $("#men_"+name).addClass("menue_sel");
      $(".contents").hide();
      $("#d_"+name).show();
    }

    function check_menu() {
      "use strict";
      var str_hash = document.location.hash.substring(1);
      if (!str_hash) {
        str_hash="overview";
      }
      if (str_hash != "overview" && str_hash != "popular" && str_hash != "charts" && str_hash != "people" && str_hash != "photos" && str_hash != "posts") {
        str_hash="overview";
      }
      menu_click(str_hash);
    }

    function find_person(id) {
      "use strict";
      var i;
      for(i = 0; i < people.length; i++) {
        if (people[i][P_ID] == id) {
          return i;
        }
      }
      return -1;
    }

    sort_function = [];

    sort_function[P_RESHARED] = function (a, b) {
      "use strict";
      var name1, name2;
      if (a[P_RESHARED] != b[P_RESHARED]) {
        return (b[P_RESHARED] - a[P_RESHARED]);
      } else {
        name1 = a[P_NAME].toUpperCase();
        name2 = b[P_NAME].toUpperCase();
        if (name1 == name2) {
          return 0;
        } else {
          if(name1 < name2) {
            return -1;
          } else {
            return 1;
          }
        }
      }
    };

    sort_function[P_RESHARES] = function (a, b) {
      "use strict";
      var name1, name2;
      if (a[P_RESHARES] != b[P_RESHARES]) {
        return (b[P_RESHARES] - a[P_RESHARES]);
      } else {
        name1 = a[P_NAME].toUpperCase();
        name2 = b[P_NAME].toUpperCase();
        if (name1 == name2) {
          return 0;
        } else {
          if(name1 < name2) {
            return -1;
          } else {
            return 1;
          }
        }
      }
    };

    sort_function[P_COMMENTS] = function (a, b) {
      "use strict";
      var name1, name2;
      if (a[P_COMMENTS] != b[P_COMMENTS]) {
        return (b[P_COMMENTS] - a[P_COMMENTS]);
      } else {
        name1 = a[P_NAME].toUpperCase();
        name2 = b[P_NAME].toUpperCase();
        if (name1 == name2) {
          return 0;
        } else {
          if(name1 < name2) {
            return -1;
          } else {
            return 1;
          }
        }
      }
    };

    sort_function[P_PLUSONES] = function (a, b) {
      "use strict";
      var name1, name2;
      if (a[P_PLUSONES] != b[P_PLUSONES]) {
        return (b[P_PLUSONES] - a[P_PLUSONES]);
      } else {
        name1 = a[P_NAME].toUpperCase();
        name2 = b[P_NAME].toUpperCase();
        if (name1 == name2) {
          return 0;
        } else {
          if(name1 < name2) {
            return -1;
          } else {
            return 1;
          }
        }
      }
    };

    function format_person(p, type) {
      "use strict";
      var str_contents;
      str_contents = "<div class=\"profile\">";
      str_contents += "<b>" + p[P_NAME] + "</b><br>";
      str_contents += "<a href=\"" + p[P_URL] + "\"><img src=\"" + p[P_PIC] + "\" style=\"width:200px;height:200px\"></a><br>";
      switch(type) {
        case P_RESHARED: str_contents += p[type].toString() + " reshare" + ((p[type] > 1) ? "s" : ""); break;
        case P_RESHARES: str_contents += p[type].toString() + " reshare" + ((p[type] > 1) ? "s" : ""); break;
        case P_COMMENTS: str_contents += p[type].toString() + " comment" + ((p[type] > 1) ? "s" : ""); break;
        case P_PLUSONES: str_contents += "+" + p[type].toString(); break;
      }
      str_contents += "</div>";
      return str_contents;
    }

    function display_people(div, type) {
      "use strict";
      var p;
      people.sort(sort_function[type]);
      $(div).html("");
      for(p = 0; p < people.length; p++) {
        if(people[p][type] > 0) {
          $(div).append(format_person(people[p],type));
        }
      }

    }

    function load_people(div, type, count, retry) {
      "use strict";
      $(".load_people").hide();
      var feed_url, jqxhr, i, item, str_type;
      if (count == 0) {
        $(div).html("");
      }
      $("#progress").html("<img src=\"<?php echo $base_url; ?>images/spinner.gif\" alt=\"spinner\"> Loading data, please wait...");
      switch(type) {
        case P_COMMENTS: str_type = "replies"; break;
        case P_RESHARES: str_type = "resharers"; break;
        case P_PLUSONES: str_type = "plusoners"; break;
      }
      if(str_type) {
        i = 0
        while(!item && i < activities.length) {
          if(!activities[i]["checked"+type]) {
            if (activities[i].object.replies) {
              if (activities[i].object.replies.totalItems > 0) {
                item = activities[i];
              }
            }
          }
          i = i + 1;
        }
        if(item) {
          feed_url = "<?php echo $base_url; ?>people.php?activity=" + item.id + "&type=" + str_type;
          jqxhr = $.getJSON(feed_url, function (data) {
            if (data.items != undefined) {
              $.each(data.items, function (i, item) {
                var p, actor_id, actor_url, actor_pic, actor_name;
                actor_id = item.id;
                p = find_person(actor_id);
                if (p < 0) {
                  actor_name = item.displayName;
                  actor_url = item.url;
                  actor_pic = "";
                  if (item.image && item.image.url) {
                    actor_pic = item.image.url;
                    actor_pic = actor_pic.replace("?sz=50","?sz=200");
                  }
                  if (actor_pic == "") {
                    actor_pic = "<?php echo $base_url; ?>images/noimage.png";
                  }
                  people.push([actor_id, actor_name, actor_url, actor_pic, 0, 0, 0, 0]);
                  p = people.length - 1;
                }
                people[p][type]++;
              });
            }
            item["checked"+type] = true;
            display_people(div, type);
            count++;
            if(count < 15) {
              setTimeout("load_people('" + div + "', " + type + ", " + count + ", 0);", 100);
            } else {
              $(".load_people").show();
            }
            $("#progress").html("");
          });
          jqxhr.error(function(xhr, status, error) {
            $("#progress").html("");
            if(retry < 5) {
              console.log("Error loading data. Attempt " + (retry+1).toString());
              setTimeout("load_people('" + div + "', " + type + ", " + count + ", " + (retry + 1).toString() + ");", 100);
            } else {
              $(".load_people").show();
              console.log("Error loading data.");
            }
          });
        }
      }
    }

    function check_reshared() {
      "use strict";
      var i, item, p, actor_id, actor_url, actor_pic, actor_name;
      for(i = 0; i < activities.length; i++) {
        item = activities[i];
        if (item.object.actor) {
          actor_id = item.object.actor.id;
          p = find_person(actor_id);
          if (p < 0) {
            actor_name = item.object.actor.displayName;
            actor_url = item.object.actor.url;
            actor_pic = "";
            if (item.object.actor.image && item.object.actor.image.url) {
              actor_pic = item.object.actor.image.url;
              actor_pic = actor_pic.replace("?sz=50","?sz=200");
            }
            if (actor_pic == "") {
              actor_pic = "<?php echo $base_url; ?>images/noimage.png";
            }
            people.push([actor_id, actor_name, actor_url, actor_pic, 0, 0, 0, 0]);
            p = people.length - 1;
          }
          people[p][P_RESHARED]++;
        }
      }
      display_people("#people_reshared", P_RESHARED);
    }

    function prepare_charts() {
      "use strict";
      var data_array, i, j, k, day, tmp_date;
      data_array = [];
      data_array.push(['Hour','Posts','Posts (o)','Posts (r)','Location','Location (o)','Location (r)','Photos','Photos (o)','Photos (r)','GIFs','GIFs (o)','GIFs (r)','Videos','Videos (o)','Videos (r)','Links','Links (o)','Links (r)','Comments','Comments (o)','Comments (r)','CpP','CpP (o)','CpP (r)','+1\'s','+1\'s (o)','+1\'s (r)','PpP','PpP (o)','PpP (r)','Reshares','Reshares (o)','Reshares (r)','RpP','RpP (o)','RpP (r)']);
      for(i = 0; i < 24; i++) {
        data_array.push([i.toString(),0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
        for(j = 0; j < S_VARS; j++) {
          for(k = 0; k < 3; k++) {
            data_array[data_array.length-1][j*3+k+1] = hour_stats[i][j][k];
          }
        }
      }
      hour_data = google.visualization.arrayToDataTable(data_array);

      data_array = [];
      data_array.push(['Weekday','Posts','Posts (o)','Posts (r)','Location','Location (o)','Location (r)','Photos','Photos (o)','Photos (r)','GIFs','GIFs (o)','GIFs (r)','Videos','Videos (o)','Videos (r)','Links','Links (o)','Links (r)','Comments','Comments (o)','Comments (r)','CpP','CpP (o)','CpP (r)','+1\'s','+1\'s (o)','+1\'s (r)','PpP','PpP (o)','PpP (r)','Reshares','Reshares (o)','Reshares (r)','RpP','RpP (o)','RpP (r)']);
      for(i = 0; i < 7; i++) {
        switch(i) {
          case 0: day = "Mon"; break;
          case 1: day = "Tue"; break;
          case 2: day = "Wed"; break;
          case 3: day = "Thu"; break;
          case 4: day = "Fri"; break;
          case 5: day = "Sat"; break;
          case 6: day = "Sun"; break;
        }
        data_array.push([day,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
        for(j = 0; j < S_VARS; j++) {
          for(k = 0; k < 3; k++) {
            data_array[data_array.length-1][j*3+k+1] = day_stats[i][j][k];
          }
        }
      }
      weekday_data = google.visualization.arrayToDataTable(data_array);

      data_array = [];
      data_array.push(['Weekday','Posts','Posts (o)','Posts (r)','Location','Location (o)','Location (r)','Photos','Photos (o)','Photos (r)','GIFs','GIFs (o)','GIFs (r)','Videos','Videos (o)','Videos (r)','Links','Links (o)','Links (r)','Comments','Comments (o)','Comments (r)','CpP','CpP (o)','CpP (r)','+1\'s','+1\'s (o)','+1\'s (r)','PpP','PpP (o)','PpP (r)','Reshares','Reshares (o)','Reshares (r)','RpP','RpP (o)','RpP (r)']);
      for(i = 0; i < 7; i++) {
        switch(i) {
          case 0: day = "Mon"; break;
          case 1: day = "Tue"; break;
          case 2: day = "Wed"; break;
          case 3: day = "Thu"; break;
          case 4: day = "Fri"; break;
          case 5: day = "Sat"; break;
          case 6: day = "Sun"; break;
        }
        data_array.push([day,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
        for(j = 0; j < S_VARS; j++) {
          for(k = 0; k < 3; k++) {
            data_array[data_array.length-1][j*3+k+1] = day_stats[i][j][k];
          }
        }
      }
      weekday_data = google.visualization.arrayToDataTable(data_array);

      data_array = [];
      data_array.push(['Date','Posts','Posts (o)','Posts (r)','Location','Location (o)','Location (r)','Photos','Photos (o)','Photos (r)','GIFs','GIFs (o)','GIFs (r)','Videos','Videos (o)','Videos (r)','Links','Links (o)','Links (r)','Comments','Comments (o)','Comments (r)','CpP','CpP (o)','CpP (r)','+1\'s','+1\'s (o)','+1\'s (r)','PpP','PpP (o)','PpP (r)','Reshares','Reshares (o)','Reshares (r)','RpP','RpP (o)','RpP (r)']);
      if (min_date) {
        tmp_date = new Date();
        tmp_date.setTime(min_date.getTime());
        while(tmp_date.getTime() < max_date.getTime() + 86400000) {
          i = tmp_date.nice_short_date();
          data_array.push([i,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
          if (daily_stats[i]) {
            for(j = 0; j < S_VARS; j++) {
              for(k = 0; k < 3; k++) {
                data_array[data_array.length-1][j*3+k+1] = daily_stats[i][j][k];
              }
            }
          }
          tmp_date.setTime(tmp_date.getTime() + 86400000);
        }
      } else {
        tmp_date = new Date();
        i = tmp_date.nice_short_date();
        data_array.push([i,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]);
      }
      day_data = google.visualization.arrayToDataTable(data_array);

      day_view = new google.visualization.DataView(day_data);
      day_view.setColumns([0,1,2,3]);
      day_chart = new google.visualization.AreaChart($("#day_chart")[0]);

      weekday_view = new google.visualization.DataView(weekday_data);
      weekday_view.setColumns([0,1,2,3]);
      weekday_chart = new google.visualization.ColumnChart($("#weekday_chart")[0]);

      hour_view = new google.visualization.DataView(hour_data);
      hour_view.setColumns([0,1,2,3]);
      hour_chart = new google.visualization.ColumnChart($("#hour_chart")[0]);

      update_charts();
    }

    function update_charts() {
      var cols = new Array();
      cols.push(0);
      if($("#chk_posts").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(1);
        if($("#chk_original").is(":checked")) cols.push(2);
        if($("#chk_reshared").is(":checked")) cols.push(3);
      }
      if($("#chk_location").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(4);
        if($("#chk_original").is(":checked")) cols.push(5);
        if($("#chk_reshared").is(":checked")) cols.push(6);
      }
      if($("#chk_photos").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(7);
        if($("#chk_original").is(":checked")) cols.push(8);
        if($("#chk_reshared").is(":checked")) cols.push(9);
      }
      if($("#chk_gifs").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(10);
        if($("#chk_original").is(":checked")) cols.push(11);
        if($("#chk_reshared").is(":checked")) cols.push(12);
      }
      if($("#chk_videos").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(13);
        if($("#chk_original").is(":checked")) cols.push(14);
        if($("#chk_reshared").is(":checked")) cols.push(15);
      }
      if($("#chk_links").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(16);
        if($("#chk_original").is(":checked")) cols.push(17);
        if($("#chk_reshared").is(":checked")) cols.push(18);
      }
      if($("#chk_comments").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(19);
        if($("#chk_original").is(":checked")) cols.push(20);
        if($("#chk_reshared").is(":checked")) cols.push(21);
      }
      if($("#chk_cpp").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(22);
        if($("#chk_original").is(":checked")) cols.push(23);
        if($("#chk_reshared").is(":checked")) cols.push(24);
      }
      if($("#chk_plusones").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(25);
        if($("#chk_original").is(":checked")) cols.push(26);
        if($("#chk_reshared").is(":checked")) cols.push(27);
      }
      if($("#chk_ppp").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(28);
        if($("#chk_original").is(":checked")) cols.push(29);
        if($("#chk_reshared").is(":checked")) cols.push(30);
      }
      if($("#chk_reshares").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(31);
        if($("#chk_original").is(":checked")) cols.push(32);
        if($("#chk_reshared").is(":checked")) cols.push(33);
      }
      if($("#chk_rpp").is(":checked")) {
        if($("#chk_total").is(":checked"))    cols.push(34);
        if($("#chk_original").is(":checked")) cols.push(35);
        if($("#chk_reshared").is(":checked")) cols.push(36);
      }
      if(cols.length>1) {
        $("#chart_warning").hide();
        $("#day_chart").show();
        $("#weekday_chart").show();
        $("#hour_chart").show();
        day_view.setColumns(cols);
        day_chart.draw(day_view,
          {width:950,
           height:250,
           title:"Timeline",
           hAxis:{textStyle:{fontSize:10}},
           legendTextStyle:{fontSize:10}}
        );
        weekday_view.setColumns(cols);
        weekday_chart.draw(weekday_view,
          {width:950,
           height:250,
           title:"Posting behaviour per weekday",
           hAxis:{textStyle:{fontSize:10}},
           legendTextStyle:{fontSize:10}}
        );
        hour_view.setColumns(cols);
        hour_chart.draw(hour_view,
          {width:950,
           height:250,
           title:"Posting behaviour per hour",
           hAxis:{textStyle:{fontSize:10}},
           legendTextStyle:{fontSize:10}}
        );
      } else {
        $("#chart_warning").show();
        $("#day_chart").hide();
        $("#weekday_chart").hide();
        $("#hour_chart").hide();
      }
    }

    function draw_map () {
      "use strict;"
      var latlng, myOptions, map, llbounds, wp, maps_marker, chk_locations, i, coords;
      latlng = new google.maps.LatLng(0, 0);
      myOptions = {
        zoom: 0,
        center: latlng,
        disableDefaultUI: true,
        zoomControl: true,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      };
      map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
      llbounds = new google.maps.LatLngBounds();
      chk_locations = false;
      for(i = 0; i < activities.length; i++) {
        if(activities[i].object.actor == undefined) {
          if(activities[i].geocode != undefined) {
            chk_locations = true;
            coords = activities[i].geocode.split(" ");
            wp = new google.maps.LatLng(coords[0], coords[1]);
            var maps_marker = new google.maps.Marker({position: wp, map: map});
            llbounds.extend(wp);
          }
        }
      }
      if (chk_locations) {
        map.fitBounds(llbounds);
      }
    }

    function update_posts() {
      var a, chk_show, item;
      for(a = 0; a < activities.length; a++) {
        item = activities[a];
        chk_show = true;
        if(!$("#posts_original").is(":checked") && item.chk_original) chk_show = false;
        if(!$("#posts_reshared").is(":checked") && item.chk_reshared) chk_show = false;
        if(!$("#posts_location").is(":checked") && item.chk_location) chk_show = false;
        if(!$("#posts_location_wo").is(":checked") && !item.chk_location) chk_show = false;
        if(!$("#posts_photos").is(":checked") && item.chk_photos) chk_show = false;
        if(!$("#posts_photos_wo").is(":checked") && !item.chk_photos) chk_show = false;
        if(!$("#posts_gifs").is(":checked") && item.chk_gifs) chk_show = false;
        if(!$("#posts_gifs_wo").is(":checked") && !item.chk_gifs) chk_show = false;
        if(!$("#posts_videos").is(":checked") && item.chk_videos) chk_show = false;
        if(!$("#posts_videos_wo").is(":checked") && !item.chk_videos) chk_show = false;
        if(!$("#posts_links").is(":checked") && item.chk_links) chk_show = false;
        if(!$("#posts_links_wo").is(":checked") && !item.chk_links) chk_show = false;
        if(!$("#posts_comments").is(":checked") && item.chk_comments) chk_show = false;
        if(!$("#posts_comments_wo").is(":checked") && !item.chk_comments) chk_show = false;
        if(!$("#posts_plusones").is(":checked") && item.chk_plusones) chk_show = false;
        if(!$("#posts_plusones_wo").is(":checked") && !item.chk_plusones) chk_show = false;
        if(!$("#posts_reshares").is(":checked") && item.chk_reshares) chk_show = false;
        if(!$("#posts_reshares_wo").is(":checked") && !item.chk_reshares) chk_show = false;

        if(chk_show) {
          $("#" + item.id).show();
        } else {
          $("#" + item.id).hide();
        }
      }
    }

    function update_stats(i) {
      var a, j, chk_r, int_type, post_time, post_hour, post_day, post_date, att, att_link, item;
      item = activities[i];
      post_time = new Date(item.published);
      post_hour = post_time.getHours();
      post_day = post_time.getDay();
      post_day = (post_day === 0) ? 6 : post_day - 1;
      post_date = post_time.nice_short_date();
      if (min_date) {
        if (post_time.getTime() < min_date.getTime()) {
          min_date = new Date();
          min_date.setFullYear(post_time.getFullYear(), post_time.getMonth(), post_time.getDate());
          min_date.setHours(12,0,0);
        }
      } else {
        min_date = new Date();
        min_date.setFullYear(post_time.getFullYear(), post_time.getMonth(), post_time.getDate());
        min_date.setHours(12,0,0);
      }
      if (max_date) {
        if (post_time.getTime() > max_date.getTime()) {
          max_date = new Date();
          max_date.setFullYear(post_time.getFullYear(), post_time.getMonth(), post_time.getDate());
          max_date.setHours(12,0,0);
        }
      } else {
        max_date = new Date();
        max_date.setFullYear(post_time.getFullYear(), post_time.getMonth(), post_time.getDate());
        max_date.setHours(12,0,0);
      }
      if (!daily_stats[post_date]) {
        daily_stats[post_date] = [];
        for (j = 0; j < S_VARS; j++) {
          daily_stats[post_date][j] = [0, 0, 0];
        }
      }
      chk_r = (item.object.actor != undefined);
      if (chk_r) {
        item.chk_reshared = true;
        item.chk_original = false;
      } else {
        item.chk_reshared = false;
        item.chk_original = true;
      }
      int_type = chk_r ? ST_RESHARED : ST_ORIGINAL;
      total_stats[S_POSTS][ST_TOTAL]++;
      total_stats[S_POSTS][int_type]++;
      hour_stats[post_hour][S_POSTS][ST_TOTAL]++;
      hour_stats[post_hour][S_POSTS][int_type]++;
      day_stats[post_day][S_POSTS][ST_TOTAL]++;
      day_stats[post_day][S_POSTS][int_type]++;
      daily_stats[post_date][S_POSTS][ST_TOTAL]++;
      daily_stats[post_date][S_POSTS][int_type]++;

      item.chk_comments = false;
      if (item.object.replies != undefined) {
        if (item.object.replies.totalItems > max_comments) {
          max_comments = item.object.replies.totalItems;
          max_comments_post = i;
        }
        if (item.object.replies.totalItems > 0) {
          item.chk_comments = true;
        }
        total_stats[S_COMMENTS][0] += item.object.replies.totalItems;
        total_stats[S_COMMENTS][int_type] += item.object.replies.totalItems;
        hour_stats[post_hour][S_COMMENTS][0] += item.object.replies.totalItems;
        hour_stats[post_hour][S_COMMENTS][int_type] += item.object.replies.totalItems;
        day_stats[post_day][S_COMMENTS][0] += item.object.replies.totalItems;
        day_stats[post_day][S_COMMENTS][int_type] += item.object.replies.totalItems;
        daily_stats[post_date][S_COMMENTS][0] += item.object.replies.totalItems;
        daily_stats[post_date][S_COMMENTS][int_type] += item.object.replies.totalItems;
      }
      item.chk_plusones = false;
      if (item.object.plusoners != undefined) {
        if (item.object.plusoners.totalItems > max_plusones) {
          max_plusones = item.object.plusoners.totalItems;
          max_plusones_post = i;
        }
        if (item.object.plusoners.totalItems > 0) {
          item.chk_plusones = true;
        }
        total_stats[S_PLUSONES][0] += item.object.plusoners.totalItems;
        total_stats[S_PLUSONES][int_type] += item.object.plusoners.totalItems;
        hour_stats[post_hour][S_PLUSONES][0] += item.object.plusoners.totalItems;
        hour_stats[post_hour][S_PLUSONES][int_type] += item.object.plusoners.totalItems;
        day_stats[post_day][S_PLUSONES][0] += item.object.plusoners.totalItems;
        day_stats[post_day][S_PLUSONES][int_type] += item.object.plusoners.totalItems;
        daily_stats[post_date][S_PLUSONES][0] += item.object.plusoners.totalItems;
        daily_stats[post_date][S_PLUSONES][int_type] += item.object.plusoners.totalItems;
      }
      item.chk_reshares = false;
      if (item.object.resharers != undefined) {
        if (item.object.resharers.totalItems > max_reshares) {
          max_reshares = item.object.resharers.totalItems;
          max_reshares_post = i;
        }
        if (item.object.resharers.totalItems > 0) {
          item.chk_reshares = true;
        }
        total_stats[S_RESHARES][0] += item.object.resharers.totalItems;
        total_stats[S_RESHARES][int_type] += item.object.resharers.totalItems;
        hour_stats[post_hour][S_RESHARES][0] += item.object.resharers.totalItems;
        hour_stats[post_hour][S_RESHARES][int_type] += item.object.resharers.totalItems;
        day_stats[post_day][S_RESHARES][0] += item.object.resharers.totalItems;
        day_stats[post_day][S_RESHARES][int_type] += item.object.resharers.totalItems;
        daily_stats[post_date][S_RESHARES][0] += item.object.resharers.totalItems;
        daily_stats[post_date][S_RESHARES][int_type] += item.object.resharers.totalItems;
      }

      item.chk_location = false;
      if (item.geocode != undefined) {
        item.chk_location = true;
        total_stats[S_LOC][ST_TOTAL]++;
        total_stats[S_LOC][int_type]++;
        hour_stats[post_hour][S_LOC][ST_TOTAL]++;
        hour_stats[post_hour][S_LOC][int_type]++;
        day_stats[post_day][S_LOC][ST_TOTAL]++;
        day_stats[post_day][S_LOC][int_type]++;
        daily_stats[post_date][S_LOC][ST_TOTAL]++;
        daily_stats[post_date][S_LOC][int_type]++;
      }
      item.chk_videos = false;
      item.chk_photos = false;
      item.chk_gifs = false;
      item.chk_links = false;
      if (item.object.attachments != undefined) {
        for (a = 0; a < item.object.attachments.length; a++) {
          att = item.object.attachments[a];
          if (att.objectType == "article") {
            item.chk_links = true;
            total_stats[S_LINKS][ST_TOTAL]++;
            total_stats[S_LINKS][int_type]++;
            hour_stats[post_hour][S_LINKS][ST_TOTAL]++;
            hour_stats[post_hour][S_LINKS][int_type]++;
            day_stats[post_day][S_LINKS][ST_TOTAL]++;
            day_stats[post_day][S_LINKS][int_type]++;
            daily_stats[post_date][S_LINKS][ST_TOTAL]++;
            daily_stats[post_date][S_LINKS][int_type]++;
          }
          if (att.objectType == "photo") {
            att_link = "";
            if (att.url != undefined) {
              att_link = att.url;
            }
            if (att_link == "") {
              if(att.fullImage != undefined) {
                att_link = att.fullImage.url
              }
            }
            if(att_link.search("plus.google.com/photos") >= 0) {
              item.chk_photos = true;
              total_stats[S_PHOTOS][ST_TOTAL]++;
              total_stats[S_PHOTOS][int_type]++;
              hour_stats[post_hour][S_PHOTOS][ST_TOTAL]++;
              hour_stats[post_hour][S_PHOTOS][int_type]++;
              day_stats[post_day][S_PHOTOS][ST_TOTAL]++;
              day_stats[post_day][S_PHOTOS][int_type]++;
              daily_stats[post_date][S_PHOTOS][ST_TOTAL]++;
              daily_stats[post_date][S_PHOTOS][int_type]++;
            }
          }
          if (att.objectType == "video") {
              item.chk_videos = true;
              total_stats[S_VIDEOS][ST_TOTAL]++;
              total_stats[S_VIDEOS][int_type]++;
              hour_stats[post_hour][S_VIDEOS][ST_TOTAL]++;
              hour_stats[post_hour][S_VIDEOS][int_type]++;
              day_stats[post_day][S_VIDEOS][ST_TOTAL]++;
              day_stats[post_day][S_VIDEOS][int_type]++;
              daily_stats[post_date][S_VIDEOS][ST_TOTAL]++;
              daily_stats[post_date][S_VIDEOS][int_type]++;
          }
          if (att.image != undefined) {
            if (att.image.url != undefined) {
              att_link = "";
              if (att.url != undefined) {
                att_link = att.url;
              }
              if (att_link == "") {
                if(att.fullImage != undefined) {
                  att_link = att.fullImage.url
                }
              }
              if(att_link.search("plus.google.com/photos") >= 0) {
                att_link = att.image.url.toUpperCase();
                att_link = att_link.substring(att_link.length - 4);
                if(att_link == ".GIF") {
                  item.chk_gifs = true;
                  total_stats[S_GIFS][ST_TOTAL]++;
                  total_stats[S_GIFS][int_type]++;
                  hour_stats[post_hour][S_GIFS][ST_TOTAL]++;
                  hour_stats[post_hour][S_GIFS][int_type]++;
                  day_stats[post_day][S_GIFS][ST_TOTAL]++;
                  day_stats[post_day][S_GIFS][int_type]++;
                  daily_stats[post_date][S_GIFS][ST_TOTAL]++;
                  daily_stats[post_date][S_GIFS][int_type]++;
                }
              }
            }
          }
        }
      }
    }

    function recalc_stats() {
      "use strict";
      var i, j;
      for (i = 0; i < 3; i++) {
        if (total_stats[S_POSTS][i] > 0) {
          total_stats[S_CPP][i] = total_stats[S_COMMENTS][i] / total_stats[S_POSTS][i];
          total_stats[S_RPP][i] = total_stats[S_RESHARES][i] / total_stats[S_POSTS][i];
          total_stats[S_PPP][i] = total_stats[S_PLUSONES][i] / total_stats[S_POSTS][i];
        }
        for (j = 0; j < 24; j++) {
          if (hour_stats[j][S_POSTS][i] > 0) {
            hour_stats[j][S_CPP][i] = hour_stats[j][S_COMMENTS][i] / hour_stats[j][S_POSTS][i];
            hour_stats[j][S_RPP][i] = hour_stats[j][S_RESHARES][i] / hour_stats[j][S_POSTS][i];
            hour_stats[j][S_PPP][i] = hour_stats[j][S_PLUSONES][i] / hour_stats[j][S_POSTS][i];
          }
        }
        for (j = 0; j < 7; j++) {
          if (day_stats[j][S_POSTS][i] > 0) {
            day_stats[j][S_CPP][i] = day_stats[j][S_COMMENTS][i] / day_stats[j][S_POSTS][i];
            day_stats[j][S_RPP][i] = day_stats[j][S_RESHARES][i] / day_stats[j][S_POSTS][i];
            day_stats[j][S_PPP][i] = day_stats[j][S_PLUSONES][i] / day_stats[j][S_POSTS][i];
          }
        }
        for (j in daily_stats) {
          if (daily_stats.hasOwnProperty(j)) {
            if (daily_stats[j][S_POSTS][i] > 0) {
              daily_stats[j][S_CPP][i] = daily_stats[j][S_COMMENTS][i] / daily_stats[j][S_POSTS][i];
              daily_stats[j][S_RPP][i] = daily_stats[j][S_RESHARES][i] / daily_stats[j][S_POSTS][i];
              daily_stats[j][S_PPP][i] = daily_stats[j][S_PLUSONES][i] / daily_stats[j][S_POSTS][i];
            }
          }
        }
      }
    }

    function display_stats() {
      $("#t_posts").html(total_stats[S_POSTS][ST_TOTAL]);
      $("#t_posts_o").html(total_stats[S_POSTS][ST_ORIGINAL]);
      $("#t_posts_r").html(total_stats[S_POSTS][ST_RESHARED]);
      $("#t_loc").html(total_stats[S_LOC][ST_TOTAL]);
      $("#t_loc_o").html(total_stats[S_LOC][ST_ORIGINAL]);
      $("#t_loc_r").html(total_stats[S_LOC][ST_RESHARED]);
      $("#t_photos").html(total_stats[S_PHOTOS][ST_TOTAL]);
      $("#t_photos_o").html(total_stats[S_PHOTOS][ST_ORIGINAL]);
      $("#t_photos_r").html(total_stats[S_PHOTOS][ST_RESHARED]);
      $("#t_gifs").html(total_stats[S_GIFS][ST_TOTAL]);
      $("#t_gifs_o").html(total_stats[S_GIFS][ST_ORIGINAL]);
      $("#t_gifs_r").html(total_stats[S_GIFS][ST_RESHARED]);
      $("#t_videos").html(total_stats[S_VIDEOS][ST_TOTAL]);
      $("#t_videos_o").html(total_stats[S_VIDEOS][ST_ORIGINAL]);
      $("#t_videos_r").html(total_stats[S_VIDEOS][ST_RESHARED]);
      $("#t_links").html(total_stats[S_LINKS][ST_TOTAL]);
      $("#t_links_o").html(total_stats[S_LINKS][ST_ORIGINAL]);
      $("#t_links_r").html(total_stats[S_LINKS][ST_RESHARED]);
      $("#t_comments").html(total_stats[S_COMMENTS][ST_TOTAL]);
      $("#t_comments_o").html(total_stats[S_COMMENTS][ST_ORIGINAL]);
      $("#t_comments_r").html(total_stats[S_COMMENTS][ST_RESHARED]);
      $("#t_cpp").html(total_stats[S_CPP][ST_TOTAL].toFixed(2));
      $("#t_cpp_o").html(total_stats[S_CPP][ST_ORIGINAL].toFixed(2));
      $("#t_cpp_r").html(total_stats[S_CPP][ST_RESHARED].toFixed(2));
      $("#t_plusones").html(total_stats[S_PLUSONES][ST_TOTAL]);
      $("#t_plusones_o").html(total_stats[S_PLUSONES][ST_ORIGINAL]);
      $("#t_plusones_r").html(total_stats[S_PLUSONES][ST_RESHARED]);
      $("#t_ppp").html(total_stats[S_PPP][ST_TOTAL].toFixed(2));
      $("#t_ppp_o").html(total_stats[S_PPP][ST_ORIGINAL].toFixed(2));
      $("#t_ppp_r").html(total_stats[S_PPP][ST_RESHARED].toFixed(2));
      $("#t_reshares").html(total_stats[S_RESHARES][ST_TOTAL]);
      $("#t_reshares_o").html(total_stats[S_RESHARES][ST_ORIGINAL]);
      $("#t_reshares_r").html(total_stats[S_RESHARES][ST_RESHARED]);
      $("#t_rpp").html(total_stats[S_RPP][ST_TOTAL].toFixed(2));
      $("#t_rpp_o").html(total_stats[S_RPP][ST_ORIGINAL].toFixed(2));
      $("#t_rpp_r").html(total_stats[S_RPP][ST_RESHARED].toFixed(2));
    }

    function reset_stats() {
      var i, j;
      max_reshares = 0;
      max_reshares_post = -1;
      max_plusones = 0;
      max_plusones_post = -1;
      max_comments = 0;
      max_comments_post = -1;
      people = [];
      total_stats = [];
      hour_stats= [];
      day_stats = [];
      daily_stats = {};
      for(j = 0; j < 24; j++) {
        hour_stats[j] = [];
      }
      for(j = 0; j < 7; j++) {
        day_stats[j] = [];
      }
      for(i = 0; i < S_VARS; i++) {
        total_stats[i] = [0, 0, 0];
        for(j = 0; j < 24; j++) {
          hour_stats[j][i] = [0, 0, 0];
        }
        for(j = 0; j < 7; j++) {
          day_stats[j][i] = [0, 0, 0];
        }
      }
    }

    function format_photo(att) {
      var str_contents, att_link, att_preview, att_title;
      str_contents = "";
      if (att.objectType == "photo") {
        att_link = "";
        att_preview = "";
        att_title = "";
        if (att.url) {
          att_link = att.url;
        }
        if (att.image) {
          att_preview = att.image.url;
        }
        if (att.displayName) {
          att_title = att.displayName;
        }
        if (att_link == "") {
          if(att.fullImage) {
            att_link = att.fullImage.url;
          }
        }
        if (att_link.search("plus.google.com/photos") >= 0) {
          if (att_title == "" && att_preview == "") {
            att_title = att_link;
          }
          if (att_preview != "") {
            str_contents += " <a href=\"" + att_link + "\">";
            str_contents += "<img src=\"" + att_preview + "\" alt=\"" + ((att_title != "") ? att_title : "preview") + "\" style=\"border:1px solid black; max-height:100px; max-width:900px;\">";
            str_contents += "</a>";
          }
        }
      }
      return str_contents;
    }

    function format_post(item) {
      var str_contents, a, chk_pic, att, att_link, att_preview, att_title;
      str_contents = "";

      if (item.object.actor != undefined) {
        chk_reshare = true;
        if (item.annotation != undefined && item.annotation != "") {
          str_contents += item.annotation + "<hr>";
        }
        str_contents += " <p class=\"smalll\">Reshared <a href=\"" + item.object.url + "\">post</a> by <a href=\"" + item.object.actor.url + "\">" + item.object.actor.displayName + "</a></p>";
      }
      str_contents += item.object.content + "<br>";
      chk_pic = false;

      if (item.object.attachments != undefined) {
        for(a = 0; a < item.object.attachments.length; a++) {
          att = item.object.attachments[a];
          att_link = "";
          att_preview = "";
          att_title = "";
          if (att.url != undefined) {
            att_link = att.url;
          }
          if (att.image != undefined) {
            att_preview = att.image.url;
          }
          if (att.displayName != undefined) {
            att_title = att.displayName;
          }
          if (att_link == "") {
            if(att.fullImage != undefined) {
              att_link = att.fullImage.url;
            }
          }
          if (att_title == "" && att_preview == "") {
            att_title = att_link;
          }
          if (att_link != "") {
            if (!(att_preview != "" && chk_pic==true)) {
              str_contents += " <br><br>";
            }
            str_contents += " <a href=\"" + att_link + "\">";
            if (att_preview != "") {
              chk_pic = true;
              str_contents += "<img src=\"" + att_preview + "\" alt=\"" + ((att_title != "") ? att_title : "preview") + "\" style=\"border:1px solid black; max-width:800px;\">";
            } else {
              str_contents += att_title;
            }
            str_contents += "</a>";
          }
        }
      }

      return str_contents;
    }

    function print_photos(i) {
      "use strict";
      var item, a, chk_reshare;

      chk_reshare = false;
      item = activities[i];
      if (item.object.actor != undefined) {
        chk_reshare = true;
      }
      if(item.object.attachments) {
        for(a = 0; a < item.object.attachments.length; a++) {
          $(chk_reshare ? "#photos_reshared" : "#photos_org").append(format_photo(item.object.attachments[a]))
        }
      }
    }

    function print_table_post(i) {
      "use strict";
      var item, str_row, post_time;

      item = activities[i];
      post_time = new Date(item.published);

      str_row = "<tr id=\"" + item.id + "\">";
      str_row += "<td sorttable_customkey=\"" + post_time.yyyymmddhhmmss() + "\" style=\"white-space: nowrap;\"><a href=\"" + item.url + "\">" + post_time.nice_date() + "<\/a><\/td>";
      if (item.object.replies != undefined) {
        str_row += "<td>" + item.object.replies.totalItems + "<\/td>";
      } else {
        str_row += "<td>0<\/td>";
      }
      if (item.object.resharers != undefined) {
        str_row += "<td>" + item.object.resharers.totalItems + "<\/td>";
      } else {
        str_row += "<td>0<\/td>";
      }
      if (item.object.plusoners != undefined) {
        str_row += "<td>" + item.object.plusoners.totalItems + "<\/td>";
      } else {
        str_row += "<td>0<\/td>";
      }
      str_row += "<td>" + format_post(item) + "<\/td>";
      str_row += "</tr>";

      $("#posts_table tbody").prepend(str_row);
    }

    function print_post(i) {
      "use strict";
      var item, str_contents, post_time, update_time;

      item = activities[i];
      post_time = new Date(item.published);
      update_time = new Date(item.updated);

      str_contents = "<p class=\"smallr\"><a href=\"" + item.url + "\">" + post_time.nice_date() + "</a>";

      if (post_time.getTime() != update_time.getTime()) {
        str_contents += " (updated " + update_time.nice_date() + ")";
      }
      str_contents += "</p>";

      str_contents += format_post(item);

      return str_contents;
    }

    function display_popular() {
      var str_contents, chk_comments, chk_reshares, chk_plusones;
      chk_comments = false;
      chk_reshares = false;
      chk_plusones = false;
      str_contents = "<br>";
      if (max_comments>0) {
        chk_comments = true;
        str_contents += "<b>Most comments (" + max_comments + ")";
        if (max_comments_post==max_reshares_post) {
          str_contents += " / Most reshares (" + max_reshares + ")";
          chk_reshares = true;
        }
        if (max_comments_post == max_plusones_post) {
          str_contents += " / Most +1's (" + max_plusones + ")";
          chk_plusones = true;
        }
        str_contents += "</b><br>";
        str_contents += print_post(max_comments_post);
      }
      if (max_reshares > 0 && chk_reshares == false) {
        chk_reshares = true;
        if (chk_comments) {
          str_contents += "<hr>";
        }
        str_contents += " <b>Most reshares (" + max_reshares + ")";
        if (max_reshares_post == max_plusones_post) {
          str_contents += " / Most +1's (" + max_plusones + ")";
          chk_plusones = true;
        }
        str_contents += "</b><br>";
        str_contents += print_post(max_reshares_post);
      }
      if (max_plusones > 0 && chk_plusones == false) {
        if (chk_comments || chk_reshares) {
          str_contents += "<hr>";
        }
        str_contents += " <b>Most +1's (" + max_plusones + ")</b><br>";
        str_contents += print_post(max_plusones_post);
      }
      str_contents += "<br><br>";

      $("#d_popular").html(str_contents);
    }

    function finish_display() {
      recalc_stats();
      display_stats();
      display_popular();
      check_reshared();
      update_posts();
      $(".load_people").show();
      if(activities.length > 0) {
        google.load("visualization", "1", {packages:["corechart"], callback: prepare_charts});
        google.load("maps", "3", {other_params:'sensor=false', callback: draw_map});
        console.log("Data loaded.");
      }
    }

    function load_activities(id, token, retry) {
      "use strict;"
      var feed_url, jqxhr, next_token;
      next_token = "";
      if (token != "") {
        feed_url = "<?php echo $base_url; ?>list.php?userid=" + id + "&token=" + token;
      } else {
        feed_url = "<?php echo $base_url; ?>list.php?userid=" + id;
      }
      jqxhr = $.getJSON(feed_url, function (data) {
        if (data.items != undefined) {
          $.each(data.items, function (i, item) {
            var act;
            activities.push(item);
            act = activities.length - 1;
            update_stats(act);
            print_table_post(act);
            print_photos(act);
          });
        }
        if (data.nextPageToken != undefined) {
          next_token = data.nextPageToken;
        }
        recalc_stats();
        display_stats();
        display_popular();
        if (next_token === "") {
          $("#progress").html("");
          finish_display();
        } else {
          setTimeout("load_activities('" + id + "', '" + next_token + "',0);", 100);
        }
      });
      jqxhr.error(function(xhr, status, error) {
        if(retry < 5) {
          console.log("Error loading data. Attempt " + (retry+1).toString());
          setTimeout("load_activities('" + id + "', '" + token + "'," + (retry+1).toString() + ");", 200 * (retry + 1));
        } else {
          console.log("Error loading data.");
          $("#progress").html("Error loading data, please reload the page to try again.");
          finish_display();
        }
      });
    }

    function load_userdata(id) {
      "use strict";
      $("#progress").html("<img src=\"<?php echo $base_url; ?>images/spinner.gif\" alt=\"spinner\"> Loading data, please wait...");
      console.log("Loading data for " + id);
      reset_stats();
      load_activities(id, "", 0);
    }

    $(function() {
      check_menu();
<?php
  if ($str_author_id != "") {
    printf("      load_userdata(\"%s\");\n",$str_author_id);
  }
?>
    });
  </script>
</head>
<body>
  <div id="header">
    <div id="header1">
      <table><tr>
        <td><form method="post" action="<?php echo $base_url; ?>">Profile ID: <input id="userid" name="userid" title="Go to a Google+ profile and copy the long number from the URL into this field."><input type="submit"></form></td>
<?php
  if(isset($authUrl)) {
    printf("        <td style=\"text-align: right;\"><a class=\"login\" href=\"%s\" title=\"Read the privacy statement for details.\">Login via Google</a> / <a href=\"%sinfo.html\">Privacy Statement &amp; Info</a></td>\n",$authUrl,$base_url);
  } else {
    printf("        <td style=\"text-align: right;\">Logged in as <a href=\"%su/%s\">%s</a> / <a class=\"logout\" href=\"?logout\">Logout</a> / <a href=\"%sinfo.html\">Privacy Statement &amp; Info</a></td>\n",$base_url,$login_id,$login_name,$base_url);
  }?>
      </tr></table>
    </div>
    <div id="header2">
      <div id="header2_info">
        <table style="width: 100%"><tr>
          <td style="width: 70px;"><img src="<?php echo $base_url; ?>images/allmy+.png" alt="All my +"></td>
          <td>
<?php
  if($str_author_name=="") {
    if($q_user!="") { ?>
            <h1>No data found.</h1>
            Please check the profile ID and note that for now only public data can be accessed via the API.<br>
            Possibly the API quota for today was exceeded. Please try again later. The quota is usually reset at 00:00 PST / 08:00 UTC
<?php
    } else { ?>
            <h1>No profile chosen.</h1>
            Use the form above to look up a specific profile or login via Google to display your own Google+ statistics.<br>
            Read the <a href=\"<?php echo $base_url; ?>info.html\">Privacy Statement &amp; Info</a> to learn more about this project.
<?php
    }
  } else { ?>
            <table><tr style="vertical-align: center;">
              <td><h1>Data for <?php echo $str_author_name; ?></h1></td>
              <td id="progress" style="vertical-align: center;"></td>
              <td style="text-align: right;"><div class="g-plusone" data-size="medium" data-annotation="inline" data-width="300"></div></td>
            </tr></table>
            <div>
              <a class="menue menue_sel" id="men_overview" href="#overview" onclick="menu_click('overview');return true;">Overview</a>
              <a class="menue menue_unsel" id="men_charts" href="#charts" onclick="menu_click('charts');return true;">Charts</a>
              <a class="menue menue_unsel" id="men_popular" href="#popular" onclick="menu_click('popular');return true;">Most popular posts</a>
              <a class="menue menue_unsel" id="men_people" href="#people" onclick="menu_click('people');return true;">People</a>
              <a class="menue menue_unsel" id="men_photos" href="#photos" onclick="menu_click('photos');return true;">Photos</a>
              <a class="menue menue_unsel" id="men_posts" href="#posts" onclick="menu_click('posts');return true;">Posts</a>
            </div>
<?php } ?>
          </td>
          <td style="text-align:right;">
        </td>
        </tr></table>
      </div>
    </div>
  </div>
  <div id="main">
<?php
  if ($str_author_name != "") {
?>
  <div id="overview" class="anchor"></div>
  <div id="popular" class="anchor"></div>
  <div id="photos" class="anchor"></div>
  <div id="charts" class="anchor"></div>
  <div id="posts" class="anchor"></div>
  <div id="people" class="anchor"></div>

  <div id="d_overview" class="contents">
    <table style="width: 100%;"><tr>
      <td style="text-align: center;">
<?php
    if($str_author_pic=="") $str_author_pic = $base_url . "images/noimage.png";
    printf("        <br><a href=\"%s\"><img src=\"%s\" alt=\"%s\" style=\"max-width:200px; max-height:200px\"></a><br>\n",$str_author_url,$str_author_pic,$str_author_name);
    printf("        <a href=\"%s\" style=\"font-weight: bold;\">%s</a>\n",$str_author_url,$str_author_name);
?>
      </td>
      <td>
        <table style="margin-left: auto; margin-right:auto;">
          <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
          <tr><th>Posts</th><td class="stats" id="t_posts"></td><td class="stats" id="t_posts_o"></td><td class="stats" id="t_posts_r"></td></tr>
          <tr><th>Location</th><td class="stats" id="t_loc"></td><td class="stats" id="t_loc_o"></td><td class="stats" id="t_loc_r"></td></tr>
          <tr><th>Photos</th><td class="stats" id="t_photos"></td><td class="stats" id="t_photos_o"></td><td class="stats" id="t_photos_r"></td></tr>
          <tr><th>GIFs</th><td class="stats" id="t_gifs"></td><td class="stats" id="t_gifs_o"></td><td class="stats" id="t_gifs_r"></td></tr>
          <tr><th>Videos</th><td class="stats" id="t_videos"></td><td class="stats" id="t_videos_o"></td><td class="stats" id="t_videos_r"></td></tr>
          <tr><th>Links</th><td class="stats" id="t_links"></td><td class="stats" id="t_links_o"></td><td class="stats" id="t_links_r"></td></tr>
          <tr><th>Comments</th><td class="stats" id="t_comments"></td><td class="stats" id="t_comments_o"></td><td class="stats" id="t_comments_r"></td></tr>
          <tr><td class="stats noborder">per post</td><td class="stats" id="t_cpp"></td><td class="stats" id="t_cpp_o"></td><td class="stats" id="t_cpp_r"></td></tr>
          <tr><th>+1's</th><td class="stats" id="t_plusones"></td><td class="stats" id="t_plusones_o"></td><td class="stats" id="t_plusones_r"></td></tr>
          <tr><td class="stats noborder">per post</td><td class="stats" id="t_ppp"></td><td class="stats" id="t_ppp_o"></td><td class="stats" id="t_ppp_r"></td></tr>
          <tr><th>Reshares</th><td class="stats" id="t_reshares"></td><td class="stats" id="t_reshares_o"></td><td class="stats" id="t_reshares_r"></td></tr>
          <tr><td class="stats noborder">per post</td><td class="stats" id="t_rpp"></td><td class="stats" id="t_rpp_o"></td><td class="stats" id="t_rpp_r"></td></tr>
        </table>
      </td>
      <td>
        <table style="margin-left: auto;">
          <tr><th style="text-align:center;">Locations of posts</th></tr>
          <tr><td class="stats noborder"><div id="map_canvas" style="width:400px; height:250px; margin-left:auto; border:1px solid black;"></div></td></tr>
        </table>
    </tr></table><br>
  </div>
  <div id="d_charts" class="contents">
    <p class="smalll">Note: All times are based on your local timezone.</p>
    <p class="smalll">
      Type: Total <input type="checkbox" id="chk_total" name="chk_total" value="chk_total" checked onclick="update_charts(); return true;"> / Original <input type="checkbox" id="chk_original" name="chk_original" value="chk_original" checked onclick="update_charts(); return true;"> / Reshared <input type="checkbox" id="chk_reshared" name="chk_reshared" value="chk_reshared" onclick="update_charts(); return true;"><br><br>
      Values: Posts <input type="checkbox" id="chk_posts" name="chk_posts" value="chk_posts" checked onclick="update_charts(); return true;">
      / Location <input type="checkbox" id="chk_location" name="chk_location" value="chk_location" onclick="update_charts(); return true;">
      / Photos <input type="checkbox" id="chk_photos" name="chk_photos" value="chk_photos" onclick="update_charts(); return true;">
      / GIFs <input type="checkbox" id="chk_gifs" name="chk_gifs" value="chk_gifs" onclick="update_charts(); return true;">
      / Videos <input type="checkbox" id="chk_videos" name="chk_videos" value="chk_videos" onclick="update_charts(); return true;">
      / Links <input type="checkbox" id="chk_links" name="chk_links" value="chk_links" onclick="update_charts(); return true;">
      / Comments <input type="checkbox" id="chk_comments" name="chk_comments" value="chk_comments" onclick="update_charts(); return true;">
      / CpP <input type="checkbox" id="chk_cpp" name="chk_cpp" value="chk_cpp" onclick="update_charts(); return true;">
      / +1's <input type="checkbox" id="chk_plusones" name="chk_plusones" value="chk_plusones" onclick="update_charts(); return true;">
      / PpP <input type="checkbox" id="chk_ppp" name="chk_ppp" value="chk_ppp" onclick="update_charts(); return true;">
      / Reshares <input type="checkbox" id="chk_reshares" name="chk_reshares" value="chk_reshares" onclick="update_charts(); return true;">
      / RpP <input type="checkbox" id="chk_rpp" name="chk_rpp" value="chk_rpp" onclick="update_charts(); return true;">
    </p>
    <div id="chart_warning" style="font-weight:bold;">No values selected.<br><br></div>
    <div id="day_chart"></div>
    <div id="weekday_chart"></div>
    <div id="hour_chart"></div>
  </div>

  <div id="d_popular" class="contents"></div>
  <div id="d_people" class="contents">
    <b>Reshared</b> - People whose posts have been reshared<br><br>
    <div style="text-align:left" id="people_reshared"></div><br>

    <b>Comments</b> - People who commented on posts<br>
    <p class="smalll">Note: Because of quota limits in the API only the 15 most recent posts with comments are used.</p>
    <div style="text-align:left" id="people_comments">
      <a class="load_people" href="#" onclick="load_people('#people_comments', P_COMMENTS, 0, 0); return false;" style="display: none";>Load people who commented</a>
    </div><br>

    <b>Resharers</b> - People who reshared posts<br>
    <p class="smalll">Note: Because of quota limits in the API only the 15 most recent posts with reshares are used.</p>
    <div style="text-align:left" id="people_reshares">
      <a class="load_people" href="#" onclick="load_people('#people_reshares', P_RESHARES, 0, 0); return false;" style="display: none";>Load people who reshared</a>
    </div><br>

    <b>+1'ers</b> - People who +1'd posts
    <p class="smalll">Note: Because of quota limits in the API only the 15 most recent posts with +1's are used.</p>
    <div style="text-align:left" id="people_plusones">
      <a class="load_people" href="#" onclick="load_people('#people_plusones', P_PLUSONES, 0, 0); return false;" style="display: none";>Load people who +1'd</a>
    </div><br>
  </div>
  <div id="d_photos" class="contents">
    <b>Photos from own posts</b><br>
    <div style="text-align:center" id="photos_org"></div><br>
    <b>Photos from reshared posts</b><br>
    <div style="text-align:center" id="photos_reshared"></div><br>
  </div>
  <div id="d_posts" class="contents">
    You can sort the table by clicking on the column headers.<br><br>
    Filter options:
    <table class="filter_table">
      <tr>
        <td>Type: Original <input type="checkbox" id="posts_original" name="posts_original" value="posts_original" checked onclick="update_posts(); return true;"> /
              Reshared <input type="checkbox" id="posts_reshared" name="posts_reshared" value="posts_reshared" checked onclick="update_posts(); return true;"></td>
        <td>Comments: With <input type="checkbox" id="posts_comments" name="posts_comments" value="posts_comments" checked onclick="update_posts(); return true;"> /
                      Without <input type="checkbox" id="posts_comments_wo" name="posts_comments_wo" value="posts_comments_wo" checked onclick="update_posts(); return true;"></td>
        <td>+1's: With <input type="checkbox" id="posts_plusones" name="posts_plusones" value="posts_plusones" checked onclick="update_posts(); return true;"> /
                  Without <input type="checkbox" id="posts_plusones_wo" name="posts_plusones_wo" value="posts_plusones_wo" checked onclick="update_posts(); return true;"></td>
        <td>Reshares: With <input type="checkbox" id="posts_reshares" name="posts_reshares" value="posts_reshares" checked onclick="update_posts(); return true;"> /
                      Without <input type="checkbox" id="posts_reshares_wo" name="posts_reshares_wo" value="posts_reshares_wo" checked onclick="update_posts(); return true;"></td>
      </tr>
      <tr>
        <td>Location: With <input type="checkbox" id="posts_location" name="posts_location" value="posts_location" checked onclick="update_posts(); return true;"> /
                  Without <input type="checkbox" id="posts_location_wo" name="posts_location_wo" value="posts_location_wo" checked onclick="update_posts(); return true;"></td>
        <td>Photos: With <input type="checkbox" id="posts_photos" name="posts_photos" value="posts_photos" checked onclick="update_posts(); return true;"> /
                Without <input type="checkbox" id="posts_photos_wo" name="posts_photos_wo" value="posts_photos_wo" checked onclick="update_posts(); return true;"></td>
        <td>GIFs: With <input type="checkbox" id="posts_gifs" name="posts_gifs" value="posts_gifs" checked onclick="update_posts(); return true;"> /
                  Without <input type="checkbox" id="posts_gifs_wo" name="posts_gifs_wo" value="posts_gifs_wo" checked onclick="update_posts(); return true;"></td>
        <td>Videos: With <input type="checkbox" id="posts_videos" name="posts_videos" value="posts_videos" checked onclick="update_posts(); return true;"> /
                    Without <input type="checkbox" id="posts_videos_wo" name="posts_videos_wo" value="posts_videos_wo" checked onclick="update_posts(); return true;"></td>
        <td>Links: With <input type="checkbox" id="posts_links" name="posts_links" value="posts_links" checked onclick="update_posts(); return true;"> /
                   Without <input type="checkbox" id="posts_links_wo" name="posts_links_wo" value="posts_links_wo" checked onclick="update_posts(); return true;"></td>
      </tr>
    </table><br>
    <table class="sortable" id="posts_table">
      <thead>
        <tr><th>Date</th><th class="sorttable_numeric">C</th><th class="sorttable_numeric">R</th><th class="sorttable_numeric">+1</th><th>Post</th></tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
<?php
  }
  if($str_errors!="") {
    $str_errors = str_replace("&amp;","&",$str_errors);
    $str_errors = str_replace("&","&amp;",$str_errors);
    printf("<div id=\"errors\" style=\"display:none\">%s</div>\n",$str_errors);
  }
?>
  </div>
<?php
  if($str_author_name!="") {
    printf("  <div id=\"footer\" class=\"footer_data\">\n");
  } else {
    printf("  <div id=\"footer\">\n");
  }
?>
    <p class="smallr">Programming by <a href="https://plus.google.com/112336147904981294875" style="color:#000000;" rel="author">Gerwin Sturm</a>, <a href="http://www.foldedsoft.at/" style="color:#000000;">FoldedSoft e.U.</a> / <a href="<?php echo $base_url;?>info.html" style="color:#000000;">Privacy Statement &amp; Info</p>
  </div>
</body>
</html>