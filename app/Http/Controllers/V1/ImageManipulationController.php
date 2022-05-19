<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\ImageManipulation;
use App\Models\Album;
use App\Http\Requests\ResizeImageRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Http\Resources\V1\ImageManipulationResource;
use Illuminate\Http\Request;

class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // return ImageManipulationResource::collection(ImageManipulation::paginate());
        return ImageManipulationResource::collection(ImageManipulation::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ResizeImageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function resize(ResizeImageRequest $request)
    {
        $all = $request->all(); 
        // $validated = $request->validated(); // i'd usually use this, as this retrieves ONLY the validated input data(unlike $request->all() which gets other stuff too like hidden inputs and stuff)

        /** @var UploadedFile|string $image */
        $image = $all['image'];
        unset($all['image']); // because later we'll save $all inside image_manipulations table's data column

        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => $request->user()->id
        ];

        if(isset($all['album_id'])) {
            $album = Album::find($all['album_id']);
            
            if($request->user()->id != $album->user_id) {
                return abort(403, 'Unauthorized');
            }
            
            $data['album_id'] = $all['album_id'];
        }

        # the guy decided to store the images under public/images instead of storage/app/public and make a symlink from public/storage to storage/app/public, like the documentation suggests https://laravel.com/docs/9.x/filesystem#the-public-disk

        // create random dir under public/images
        $dir = 'images/' . Str::random() . '/'; // this is the relative path
        $absolutePath = public_path($dir);
        File::makeDirectory($absolutePath);

        // public/images/asidhf32rhh/test.jpg
        // public/images/asidhf32rhh/test-resized.jpg
        if($image instanceof \Illuminate\Http\UploadedFile) {
            $data['name'] = $image->getClientOriginalName();            
            $filename = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();            

            $image->move($absolutePath, $data['name']);
        } else { // image is url
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);

            copy($image, $absolutePath . $data['name']); // https://www.php.net/manual/en/function.copy
        }
        $data['path'] = $dir . $data['name'];
        $originalPath = $absolutePath . $data['name'];


        # resize
        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height, $image) = $this->getImageWidthAndHeight($w, $h, $originalPath); // https://www.php.net/manual/en/function.list.php

        $resizedFilenameWithExt = $filename . '-resized.' . $extension;
        $image->resize($width, $height)->save($absolutePath . $resizedFilenameWithExt);
        $data['output_path'] = $dir . $resizedFilenameWithExt;

        # create ImageManipulation db record
        $imageManipulation = ImageManipulation::create($data);

        // return $imageManipulation;
        return new ImageManipulationResource($imageManipulation);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImageManipulation  $image
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ImageManipulation $image)
    {
        if($request->user()->id != $image->user_id) {
            return abort(403, 'Unauthorized');
        }

        return new ImageManipulationResource($image);
    }

    /**
     * Display the specified resource by album.
     *
     * @param  \App\Models\Album $album
     * @return \Illuminate\Http\Response
     */
    public function byAlbum(Request $request, Album $album)
    {
        if($request->user()->id != $album->user_id) {
            return abort(403, 'Unauthorized');
        }

        $where = ['album_id' => $album->id];
        return ImageManipulationResource::collection(ImageManipulation::where($where)->paginate());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImageManipulation  $image
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ImageManipulation $image)
    {
        if($request->user()->id != $image->user_id) {
            return abort(403, 'Unauthorized');
        }

        $image->delete();

        return response('', 204);
    }

    /**
     * Get the image's new width and height, in px
     * 
     * @param string|float $w can be speficified in px as a number(without 'px') or percentage
     * @param string|float $h OPTIONAL can be speficified in px as a number(without 'px') or percentage
     * both $w and $h will be either px or % values, not both(we don't support that case)
     * @param string $originalPath
     * 
     * @return array returns the new width and height in pixels as float numbers(without 'px'), and the image
     */
    protected function getImageWidthAndHeight($w, $h, string $originalPath)
    {
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if(str_ends_with($w, '%')) {
            $ratioW = (float)str_replace('%', '', $w);
            $ratioH = $h ? (float)str_replace('%', '', $h) : $ratioW;

            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float)$w;
            $newHeight = $h ? (float) $h : $originalHeight * $newWidth / $originalWidth;
        }

        return [$newWidth, $newHeight, $image];
    }
}
