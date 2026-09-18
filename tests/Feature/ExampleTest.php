<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * DN Unity kapali devredir: ana sayfa herkese acik bir tanitim degil,
     * girisi olmayani giris ekranina gonderen bir kapidir.
     */
    public function test_ana_sayfa_misafiri_girise_yonlendirir(): void
    {
        $this->get('/')->assertRedirect(route('giris'));
    }

    public function test_saglik_kontrolu_calisir(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}
