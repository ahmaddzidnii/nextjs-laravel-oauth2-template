import Link from "next/link";
import { Metadata } from "next";

import { Card } from "@/components/ui/card";
import RegisterForm from "@/features/auth/components/RegisterForm";
import SocialLoginComponent from "@/features/auth/components/SocialLoginComponent";

export const metadata: Metadata = {
  title: "Login Page",
  abstract: "Login page for Next.js + Laravel Oauth2",
};

export default function RegisterPage() {
  return (
    <div className=" w-full h-screen flex justify-center items-center">
      <Card className="w-full max-w-screen-lg overflow-hidden rounded-[25px]">
        <div className="grid grid-cols-12 ">
          <div className="col-span-12 md:col-span-7 px-12 py-6 flex flex-col gap-5 justify-center items-center">
            <h1 className="text-2xl font-bold text-center">Create an account in this app</h1>
            <RegisterForm />
            <div className="flex items-center justify-center w-full space-x-4">
              <div className="flex-grow h-[1px] bg-gray-300"></div>
              <span className="text-gray-500 text-sm">or continue with</span>
              <div className="flex-grow h-[1px] bg-gray-300"></div>
            </div>
            <SocialLoginComponent />
            <p className="text-sm">
              Already have an account? &nbsp;
              <Link
                className=" text-primary font-bold"
                href="/auth/login"
              >
                Login
              </Link>
            </p>
          </div>
          <div className="col-span-5 justify-center items-center  bg-gradient-to-br from-[#f77c08] to-[#fbad61] hidden md:flex">
            <div className="bg-cover bg-center bg-[url('/img/login-vector.png')] aspect-square size-64" />
          </div>
        </div>
      </Card>
    </div>
  );
}
