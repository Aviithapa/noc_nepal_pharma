<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\Inquiry\CreateInquiryRequest;
use App\Models\ResultUpload;
use App\Repositories\CMS\Post\PostRepository;
use App\Repositories\College\CollegeRepository;
use App\Repositories\Contact\ContactRepository;
use App\Repositories\Inquiry\InquiryRepository;
use App\Repositories\News\NewsRepository;
use App\Repositories\NewsLetterSubscribe\NewsLetterSubscribeRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
    //

    private $viewData;
    private $postRepository;
    private $request;
    private $inquiryRepository;
    private $newsLetterSubscribeRepository;
    private $contactRepository;
    private $newsRepository;
    private $collegeRepository;



    public function __construct(
        Request $request,
        PostRepository $postRepository,
        InquiryRepository $inquiryRepository,
        NewsLetterSubscribeRepository $newsLetterSubscribeRepository,
        ContactRepository $contactRepository,
        NewsRepository $newsRepository,
        CollegeRepository $collegeRepository
    ) {
        $this->request = $request;
        $this->postRepository = $postRepository;
        $this->inquiryRepository = $inquiryRepository;
        $this->newsLetterSubscribeRepository = $newsLetterSubscribeRepository;
        $this->contactRepository = $contactRepository;
        $this->newsRepository = $newsRepository;
        $this->collegeRepository = $collegeRepository;
        parent::__construct();
    }


    public function slug( Request $request, $slug = null)
    {
        $slug = $slug ? $slug : 'noc-online';
        $this->viewData['heading'] = $request->query('heading') ?? ''; // Get the 'param' from the query string
        $file_path = resource_path() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'website/pages' . DIRECTORY_SEPARATOR . $slug . '.blade.php';
        $this->viewData['pageData'] = $this->postRepository->findBy('slug', $slug);
        $this->viewData['clients'] = $this->postRepository->all()->where('type', 'clients');
        $this->viewData['slug'] = $slug;
        $this->viewData['heading_team'] = $this->postRepository->findBy('id', 19, ['title', 'content', 'excerpt']);
        $this->viewData['heading_blog'] = $this->postRepository->findBy('id', 20, ['title', 'content', 'excerpt']);
        $this->viewData['discount'] = $this->postRepository->findBy('id', 15, ['title', 'image', 'content', 'excerpt']);
        if (file_exists($file_path) && $this->viewData['pageData']) {
            switch ($slug) {
                default:
                    return view('website.pages.' . $slug,  $this->viewData);
                break;
            }
            return view('website.pages.' . $slug,  $this->viewData);
        }
        return view('website.pages.404');
    }

}
