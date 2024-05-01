<?php

namespace aodihis\productreview\web\twig;

use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;

class ProductReviewVariable
{
    public function getReviewById(int $id): ?Review
    {  
        return Plugin::getInstance()->getReviews()->getReviewById($id);

    }
}