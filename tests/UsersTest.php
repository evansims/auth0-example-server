<?php

class UsersTest extends TestCase
{
    /**
     * Ensure tokenless requests are rejected.
     *
     * @return void
     */
    public function testUnauthorized()
    {
        $this->get('/users');

        $this->assertEquals(
            'Unauthorized.', $this->response->getContent()
        );
    }

    /**
     * Test the /users endpoint for results.
     *
     * @return void
     */
    public function testListUsersReturned()
    {
        $this->withoutMiddleware();

        $this->get('/users');

        $this->assertStringContainsString(
            '},"data":[', $this->response->getContent()
        );
    }
}
