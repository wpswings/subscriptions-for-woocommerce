<?php 

class HomeCheckCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo("go on site home page");
        $I->amOnPage('/');
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        $blogname = $I->grabFromDatabase('wp_options', 'option_value', ['option_name' => 'blogname']);
        $I->wantTo("check if ".$blogname." available in source" );

        $I->seeInSource( $blogname );
    }

    public function tryAnother(AcceptanceTester $I)
    {
        $siteurl = $I->grabFromDatabase('wp_options', 'option_value', ['option_name' => 'siteurl']);
        $I->wantTo("check the siteurl " . $siteurl);
    }
}
