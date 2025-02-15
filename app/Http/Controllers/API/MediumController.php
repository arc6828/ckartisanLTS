<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Medium;
use Illuminate\Http\Request;
use Vedmant\FeedReader\Facades\FeedReader;
use Illuminate\Support\Str;

class MediumController extends Controller
{

    public function update($publication = "ckartisan", $tagname = "")
    {
        $data = Medium::fetch($publication, $tagname);
        
        // save to db
        foreach ($data->channel->item as $item) {            
            Medium::updateOrCreate(
                ['guid' => (string)$item->guid], 
                [
                    'slug' => urldecode(basename(parse_url($item->link, PHP_URL_PATH))) ,
                    'image' => $item->image_url, // บันทึก HTML Content
                    'first_paragraph' => $item->first_paragraph,
                    'publication' => $publication,
                    'category' => $item->category,
                    'data' => $item,
                ]
            );
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function index($publication = "ckartisan", $tagname = "")
    {
        $data = Medium::where('publication',$publication)
            ->where('category','like',"%{$tagname}%")
            ->select('id','guid','slug','image','first_paragraph','publication','category')
            ->get();
        return response()->json($data);
    }

    public function feed($publication = "ckartisan", $tagname = "")
    {
        $data = Medium::fetch($publication, $tagname);
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function feedNoCache($publication = "ckartisan", $tagname = "")
    {
        $data = Medium::fetchNoCache($publication, $tagname);
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
