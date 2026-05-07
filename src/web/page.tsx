import React from 'react';
import Dashboard from "./components/Dashboard";
import {AuthGuard} from "@vatts/auth/react";
import {Metadata} from "vatts/react";

export default function Welcome() {

  return (
    <AuthGuard redirectTo={"/auth"}>
      <Dashboard></Dashboard>
    </AuthGuard>
  );
}

export function generateMetadata(): Metadata {
    return {
        title: "Dashboard",
    }
}