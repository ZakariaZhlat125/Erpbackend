<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="ERP V2 API",
 *     version="1.0.0",
 *     description="Comprehensive ERP System API Documentation with Multi-tenancy and Role-based Access Control",
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Organizations",
 *     description="Organization management"
 * )
 * 
 * @OA\Tag(
 *     name="Branches",
 *     description="Branch management"
 * )
 * 
 * @OA\Tag(
 *     name="Parties",
 *     description="Customer/Supplier management"
 * )
 * 
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice management"
 * )
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="Product catalog management"
 * )
 * 
 * @OA\Tag(
 *     name="Accounts",
 *     description="Chart of accounts management"
 * )
 * 
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment tracking"
 * )
 * 
 * @OA\Tag(
 *     name="Warehouses",
 *     description="Warehouse management"
 * )
 * 
 * @OA\Tag(
 *     name="Employees",
 *     description="HR employee management"
 * )
 * 
 * @OA\Tag(
 *     name="Projects",
 *     description="Project management"
 * )
 * 
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task tracking"
 * )
 */
abstract class Controller
{
    //
}
