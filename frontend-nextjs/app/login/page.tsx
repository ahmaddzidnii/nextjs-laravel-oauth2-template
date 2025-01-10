import { Metadata } from "next";
import LoginButton from "@/app/components/LoginButton";

export const metadata: Metadata = {
  title: "Login Page",
  abstract: "Login page for Next.js + Laravel Oauth2",
};

export default function LoginPage() {
  return (
    <div className=" w-full h-screen flex justify-center items-center">
      <div className="w-[370px] bg-gray-200 flex flex-col justify-center items-center rounded-lg p-4 shadow-sm">
        <h1 className="text-4xl font-bold">Login Page</h1>

        <LoginButton />
      </div>
    </div>
  );
}
