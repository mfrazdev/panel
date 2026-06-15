import React from 'react';
import Dashboard from "./components/Dashboard";
import {AuthGuard} from "@nytlex/auth/react";
import {Metadata} from "nytlex/react";

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