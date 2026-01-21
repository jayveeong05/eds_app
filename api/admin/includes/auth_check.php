<?php
/**
 * Auth Check - JWT Token Based
 * 
 * Authentication is now handled client-side via JWT tokens stored in sessionStorage.
 * The auth.js file automatically redirects to login if no token is present.
 * 
 * PHP session-based authentication is disabled for Vercel serverless compatibility.
 */
 
// No server-side session check needed - JWT validation happens in JavaScript
